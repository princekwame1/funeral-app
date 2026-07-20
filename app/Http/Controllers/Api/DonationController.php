<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\DonationConfirmer;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DonationController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly DonationConfirmer $confirmer,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Donation::query()->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $donations = $query->paginate(20);

        return response()->json($donations);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'donor_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'in:online,offline'],
            'provider' => ['nullable', 'string', 'in:mtn,vod,tgo,atl'],
        ]);

        $user = $request->user();

        $amountMinor = (int) round(((float) $data['amount']) * 100);
        $currency = (string) config('services.paystack.default_currency', 'GHS');
        $method = $data['payment_method'] ?? Donation::METHOD_ONLINE;

        if ($method === Donation::METHOD_OFFLINE) {
            $donation = Donation::create([
                'user_id' => $user->id,
                'donor_name' => $data['donor_name'],
                'phone' => $data['phone'],
                'amount' => $amountMinor,
                'currency' => $currency,
                'payment_method' => Donation::METHOD_OFFLINE,
                'status' => Donation::STATUS_PAID,
                'paystack_channel' => 'cash',
                'gateway_response' => 'Recorded offline',
                'paid_at' => Carbon::now(),
            ]);

            $this->confirmer->sendThankYou($donation);

            return response()->json([
                'donation' => $donation->fresh(),
            ], 201);
        }

        $provider = $data['provider'] ?? (string) config('services.paystack.default_provider', 'mtn');

        $charge = $this->paystack->chargeMobileMoney(
            email: (string) config('services.paystack.callback_email'),
            amountMinor: $amountMinor,
            phone: $data['phone'],
            provider: $provider,
            currency: $currency,
        );

        $donation = Donation::create([
            'user_id' => $user->id,
            'donor_name' => $data['donor_name'],
            'phone' => $data['phone'],
            'amount' => $amountMinor,
            'currency' => $currency,
            'payment_method' => Donation::METHOD_ONLINE,
            'status' => $charge['ok'] ? Donation::STATUS_PENDING : Donation::STATUS_FAILED,
            'paystack_reference' => $charge['reference'],
            'paystack_channel' => 'mobile_money:' . $provider,
            'gateway_response' => data_get($charge, 'body.data.display_text')
                ?? data_get($charge, 'body.data.gateway_response')
                ?? data_get($charge, 'body.message'),
        ]);

        return response()->json([
            'donation' => $donation,
            'paystack' => $charge['body'],
        ], $charge['ok'] ? 201 : 422);
    }

    public function show(Request $request, Donation $donation): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $donation->user_id !== $user->id) {
            abort(403);
        }

        return response()->json(['donation' => $donation]);
    }

    public function verify(Request $request, Donation $donation, DonationConfirmer $confirmer): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $donation->user_id !== $user->id) {
            abort(403);
        }

        if (! $donation->paystack_reference) {
            return response()->json(['message' => 'No reference to verify'], 422);
        }

        $result = $this->paystack->verify($donation->paystack_reference);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'message' => 'Paystack verify call failed',
                'paystack_status_code' => $result['status'] ?? null,
                'paystack_body' => $result['body'] ?? null,
            ], 502);
        }

        $status = data_get($result, 'body.data.status');
        $gatewayMessage = data_get($result, 'body.data.gateway_response')
            ?? data_get($result, 'body.data.message');

        if ($status === 'success' && $donation->status !== Donation::STATUS_PAID) {
            $confirmer->markPaid($donation, $result['body']);
        } elseif (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
            $donation->update([
                'status' => Donation::STATUS_FAILED,
                'gateway_response' => $gatewayMessage ?? $donation->gateway_response,
            ]);
        }

        return response()->json([
            'donation' => $donation->fresh(),
            'paystack_status' => $status,
        ]);
    }
}
