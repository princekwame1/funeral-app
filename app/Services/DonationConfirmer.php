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

        return sprintf(
            "Dear %s, thank you for your kind contribution of %s %s to the family. May God bless you. Ref: %s",
            $firstName,
            $donation->currency,
            $amountMajor,
            $reference
        );
    }
}
