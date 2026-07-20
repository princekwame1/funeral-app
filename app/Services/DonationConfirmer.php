<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DonationConfirmer
{
    public function __construct(private readonly SmsService $sms)
    {
    }

    public function markPaid(Donation $donation, array $paystackBody): void
    {
        $donation->update([
            'status' => Donation::STATUS_PAID,
            'gateway_response' => data_get($paystackBody, 'data.gateway_response') ?? $donation->gateway_response,
            'paid_at' => Carbon::now(),
        ]);

        $this->sendThankYou($donation);
    }

    public function sendThankYou(Donation $donation): void
    {
        if ($donation->sms_sent) {
            return;
        }

        $donationId = $donation->id;
        $phone = $donation->phone;
        $message = $this->buildMessage($donation);
        $sms = $this->sms;

        // Defer to run after the HTTP response is flushed to the browser.
        // The user sees the redirect instantly; the SMS call happens in the
        // same PHP worker but on a background tick, so a slow TextTango call
        // doesn't block the loading spinner.
        dispatch(function () use ($sms, $donationId, $phone, $message) {
            try {
                $fresh = Donation::withoutGlobalScopes()->find($donationId);
                if (! $fresh || $fresh->sms_sent) return;
                if ($sms->send($phone, $message)) {
                    $fresh->update(['sms_sent' => true]);
                }
            } catch (\Throwable $e) {
                Log::warning('Thank-you SMS failed', [
                    'donation_id' => $donationId,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    private function buildMessage(Donation $donation): string
    {
        $amountMajor = number_format($donation->amount / 100, 2);
        $reference = $donation->paystack_reference ?? 'OFF-' . $donation->id;
        $firstName = strtok(trim($donation->donor_name), ' ') ?: $donation->donor_name;

        $tenant = $donation->tenant_id
            ? \App\Models\Tenant::find($donation->tenant_id)
            : null;

        // Prefer the tenant's editable thank-you template (sms_templates table).
        $body = self::defaultTemplate();
        if ($tenant) {
            $template = \App\Models\SmsTemplate::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('kind', \App\Models\SmsTemplate::KIND_THANKYOU)
                ->where('is_default', true)
                ->first();
            if ($template) {
                $body = $template->body;
            } elseif ($tenant->thankyou_template) {
                // Legacy column fallback for tenants seeded before the sms_templates table.
                $body = $tenant->thankyou_template;
            }
        }

        return strtr($body, [
            '[DONOR]' => $firstName,
            '[NAME]' => $firstName,
            '[FULL_NAME]' => $donation->donor_name,
            '[AMOUNT]' => "{$donation->currency} {$amountMajor}",
            '[CURRENCY]' => $donation->currency,
            '[REFERENCE]' => $reference,
            '[FAMILY]' => $tenant?->family_name ?? '',
            '[DECEASED]' => $tenant?->deceased_name ?? '',
            '[TENANT]' => $tenant?->name ?? '',
        ]);
    }

    public static function defaultTemplate(): string
    {
        return 'Dear [DONOR], thank you for your kind contribution of [AMOUNT] to the family. May God bless you.';
    }

    public static function availableTokens(): array
    {
        return [
            '[DONOR]' => "Donor's first name",
            '[FULL_NAME]' => "Donor's full name",
            '[AMOUNT]' => 'Amount with currency (e.g. GHS 50.00)',
            '[CURRENCY]' => 'Currency code (e.g. GHS)',
            '[REFERENCE]' => 'Payment / receipt reference',
            '[FAMILY]' => 'Family name (from Funeral page)',
            '[DECEASED]' => "Deceased's name (from Funeral page)",
            '[TENANT]' => 'Tenant name',
        ];
    }
}
