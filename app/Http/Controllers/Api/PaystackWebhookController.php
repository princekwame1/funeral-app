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

        // Global TenantScope is not applied here — CurrentTenant is unset for public webhook.
        $donation = Donation::where('paystack_reference', $reference)->first();
        if (! $donation) {
            return response()->json(['message' => 'Unknown reference'], 202);
        }

        $gatewayMessage = data_get($payload, 'data.gateway_response')
            ?? data_get($payload, 'data.message');

        if ($event === 'charge.success' && $donation->status !== Donation::STATUS_PAID) {
            $confirmer->markPaid($donation, $payload);
        } elseif (in_array($event, ['charge.failed', 'charge.abandoned', 'charge.dispute.create'], true)) {
            $donation->update([
                'status' => Donation::STATUS_FAILED,
                'gateway_response' => $gatewayMessage ?? $donation->gateway_response,
            ]);
        } elseif ($event === 'refund.processed' && $donation->status === Donation::STATUS_PAID) {
            $donation->update([
                'status' => Donation::STATUS_FAILED,
                'gateway_response' => 'Refunded' . ($gatewayMessage ? ": {$gatewayMessage}" : ''),
            ]);
        }

        return response()->json(['message' => 'ok']);
    }
}
