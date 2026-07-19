<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\DonationConfirmer;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack, DonationConfirmer $confirmer): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');
        $raw = $request->getContent();

        if (! $paystack->verifySignature($raw, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->json()->all();
        $event = data_get($payload, 'event');
        $reference = data_get($payload, 'data.reference');

        if (! $reference) {
            return response()->json(['message' => 'No reference'], 202);
        }

        $donation = Donation::where('paystack_reference', $reference)->first();
        if (! $donation) {
            return response()->json(['message' => 'Unknown reference'], 202);
        }

        if ($event === 'charge.success') {
            $confirmer->markPaid($donation, $payload);
        } elseif (in_array($event, ['charge.failed', 'charge.dispute.create'], true)) {
            $donation->update(['status' => Donation::STATUS_FAILED]);
        }

        return response()->json(['message' => 'ok']);
    }
}
