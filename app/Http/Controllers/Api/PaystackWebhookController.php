<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\WebhookEvent;
use App\Services\DonationConfirmer;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack, DonationConfirmer $confirmer): JsonResponse
    {
        $raw = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $sigOk = $paystack->verifySignature($raw, $signature);

        $payload = $request->json()->all();
        $event = data_get($payload, 'event');
        $reference = data_get($payload, 'data.reference');

        $log = WebhookEvent::create([
            'provider' => 'paystack',
            'event' => $event,
            'reference' => $reference,
            'signature_ok' => $sigOk,
            'payload' => $raw,
            'received_at' => now(),
        ]);

        if (! $sigOk) {
            $log->update(['response_status' => 401, 'response_body' => 'Invalid signature']);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if (! $reference) {
            $log->update(['response_status' => 202, 'response_body' => 'No reference']);
            return response()->json(['message' => 'No reference'], 202);
        }

        // Global TenantScope isn't applied here — CurrentTenant is unset for public webhook.
        $donation = Donation::where('paystack_reference', $reference)->first();
        if (! $donation) {
            $log->update(['response_status' => 202, 'response_body' => 'Unknown reference']);
            return response()->json(['message' => 'Unknown reference'], 202);
        }

        try {
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
        } catch (\Throwable $e) {
            $log->update(['response_status' => 500, 'error' => $e->getMessage()]);
            throw $e;
        }

        $log->update(['response_status' => 200, 'response_body' => 'ok']);
        return response()->json(['message' => 'ok']);
    }
}
