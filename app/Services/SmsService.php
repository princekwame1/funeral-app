<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send a single transactional SMS (donation confirmations, OTP-style).
     * https://app.texttango.com/docs/api/v2  →  POST /sms/transactional/send
     */
    public function send(string $phone, string $message): bool
    {
        if (! $this->configured()) {
            Log::info('SMS not configured; skipping send', ['phone' => $phone]);
            return false;
        }

        $to = $this->toE164($phone);
        if ($to === '') {
            Log::warning('SMS skipped: invalid phone', ['phone' => $phone]);
            return false;
        }

        try {
            $response = $this->client()->post('/sms/transactional/send', [
                'to' => $to,
                'from' => $this->senderId(),
                'message' => $message,
            ]);

            if (! $response->successful()) {
                Log::warning('SMS send failed', [
                    'phone' => $to,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send exception', ['phone' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send the same message to many recipients via a single TextTango campaign.
     * https://app.texttango.com/docs/api/v2  →  POST /campaigns   (up to 10,000 recipients)
     *
     * @param  iterable<string>  $phones
     * @return array{sent:int, failed:int, skipped:int, total:int, failures:array<int,string>, campaign_id?:?string}
     */
    public function sendBulk(iterable $phones, string $message, ?string $campaignName = null): array
    {
        $recipients = [];
        $skipped = 0;
        $seen = [];

        foreach ($phones as $raw) {
            $e164 = $this->toE164((string) $raw);
            if ($e164 === '' || isset($seen[$e164])) {
                $skipped++;
                continue;
            }
            $seen[$e164] = true;
            $recipients[] = $e164;
        }

        $total = count($recipients);

        if ($total === 0) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => $skipped, 'total' => 0, 'failures' => []];
        }

        if (! $this->configured()) {
            Log::info('SMS not configured; skipping bulk send', ['count' => $total]);
            return ['sent' => 0, 'failed' => $total, 'skipped' => $skipped, 'total' => $total, 'failures' => $recipients];
        }

        try {
            $payload = array_filter([
                'from' => $this->senderId(),
                'body' => $message,
                'to' => $recipients,
                'campaign_name' => $campaignName,
            ], fn ($v) => $v !== null);

            $response = $this->client()->post('/campaigns', $payload);

            if (! $response->successful()) {
                Log::warning('SMS bulk campaign failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'recipient_count' => $total,
                ]);
                return ['sent' => 0, 'failed' => $total, 'skipped' => $skipped, 'total' => $total, 'failures' => $recipients];
            }

            $body = $response->json();

            return [
                'sent' => $total,
                'failed' => 0,
                'skipped' => $skipped,
                'total' => $total,
                'failures' => [],
                'campaign_id' => data_get($body, 'data.id'),
            ];
        } catch (\Throwable $e) {
            Log::error('SMS bulk exception', ['error' => $e->getMessage(), 'count' => $total]);
            return ['sent' => 0, 'failed' => $total, 'skipped' => $skipped, 'total' => $total, 'failures' => $recipients];
        }
    }

    // --- TextTango contact & group sync ------------------------------------

    public function createContactGroup(string $name, ?string $description = null): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->post('/contact-groups', array_filter([
            'name' => $name,
            'description' => $description,
        ]));
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    public function updateContactGroup(string $providerId, array $fields): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->patch('/contact-groups/' . rawurlencode($providerId), $fields);
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    public function deleteContactGroup(string $providerId): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->delete('/contact-groups/' . rawurlencode($providerId));
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    public function createContact(string $phone, ?string $firstName = null, ?string $lastName = null, ?string $email = null, array $groupProviderIds = []): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->post('/contacts', array_filter([
            'phone' => $this->toE164($phone),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'contact_group_ids' => $groupProviderIds ?: null,
        ], fn ($v) => $v !== null && $v !== ''));
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    public function bulkCreateContacts(array $rows): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->post('/contacts/bulk', ['contacts' => $rows]);
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    public function deleteContact(string $providerId): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->delete('/contacts/' . rawurlencode($providerId));
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    public function sendCampaignToGroups(array $groupProviderIds, string $message, ?string $campaignName = null): array
    {
        if (! $this->configured()) return ['ok' => false, 'body' => null];
        $r = $this->client()->post('/campaigns', array_filter([
            'from' => $this->senderId(),
            'body' => $message,
            'contact_group_ids' => $groupProviderIds,
            'campaign_name' => $campaignName,
        ], fn ($v) => $v !== null));
        return ['ok' => $r->successful(), 'body' => $r->json() ?? []];
    }

    private function client()
    {
        return Http::withToken((string) config('services.sms.api_key'))
            ->baseUrl((string) config('services.sms.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function configured(): bool
    {
        return (string) config('services.sms.api_key') !== ''
            && (string) config('services.sms.base_url') !== '';
    }

    private function senderId(): string
    {
        $tenant = app(\App\Support\CurrentTenant::class)->get();
        if ($tenant && $tenant->sms_sender_id) {
            return (string) $tenant->sms_sender_id;
        }

        return (string) config('services.sms.sender_id', 'Funeral');
    }

    /**
     * Normalize a phone to E.164 using the configured default country code
     * (defaults to Ghana: 233). Accepts inputs like "0244123456", "233244123456",
     * "+233 244 123 456", "244123456".
     */
    private function toE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        $cc = ltrim((string) config('services.sms.default_country_code', '233'), '+');

        if (str_starts_with($digits, $cc)) {
            $national = substr($digits, strlen($cc));
        } elseif (str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
        } else {
            $national = $digits;
        }

        if ($national === '' || strlen($national) < 7) {
            return '';
        }

        return '+' . $cc . $national;
    }
}
