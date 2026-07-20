<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\SmsCampaign;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TextTangoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        // TextTango signs webhooks with X-TextTango-Signature (HMAC-SHA256 over the raw body)
        // using the tenant's SMS_API_KEY. In multi-tenant mode we verify against the platform key
        // and only accept if it matches; per-tenant matching is a future enhancement.
        $signature = $request->header('x-texttango-signature');
        $secret = (string) config('services.sms.api_key');
        $sigOk = $signature && $secret
            && hash_equals(hash_hmac('sha256', $raw, $secret), $signature);

        $payload = $request->json()->all();
        $event = data_get($payload, 'event');
        $reference = data_get($payload, 'data.reference')
            ?? data_get($payload, 'data.message_id')
            ?? data_get($payload, 'data.campaign_id');

        $log = WebhookEvent::create([
            'provider' => 'texttango',
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

        try {
            switch ($event) {
                case 'message.delivered':
                case 'message.sent':
                    // Transactional per-recipient confirmation. TextTango returns the
                    // recipient phone; find the matching pending thank-you donation.
                    $phone = data_get($payload, 'data.to') ?? data_get($payload, 'data.recipient');
                    if ($phone) {
                        Donation::withoutGlobalScopes()
                            ->where('phone', $phone)
                            ->where('sms_sent', false)
                            ->orderBy('id', 'desc')
                            ->limit(1)
                            ->update(['sms_sent' => true]);
                    }
                    break;

                case 'message.failed':
                case 'message.undelivered':
                    // Nothing to flip — donation stays sms_sent=false so it shows "Not sent".
                    break;

                case 'campaign.completed':
                case 'campaign.finished':
                    $providerId = data_get($payload, 'data.id') ?? data_get($payload, 'data.campaign_id');
                    if ($providerId) {
                        $c = SmsCampaign::withoutGlobalScopes()
                            ->where('provider_campaign_id', $providerId)
                            ->first();
                        if ($c) {
                            $c->update([
                                'sent_count' => (int) data_get($payload, 'data.delivered', $c->sent_count),
                                'failed_count' => (int) data_get($payload, 'data.failed', $c->failed_count),
                                'status' => (data_get($payload, 'data.failed', 0) > 0)
                                    ? SmsCampaign::STATUS_PARTIAL
                                    : SmsCampaign::STATUS_SENT,
                            ]);
                        }
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $log->update(['response_status' => 500, 'error' => $e->getMessage()]);
            throw $e;
        }

        $log->update(['response_status' => 200, 'response_body' => 'ok']);
        return response()->json(['message' => 'ok']);
    }
}
