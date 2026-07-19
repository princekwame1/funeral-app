<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Carbon;

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

        $amountMajor = number_format($donation->amount / 100, 2);
        $reference = $donation->paystack_reference ?? 'OFF-' . $donation->id;
        $firstName = strtok(trim($donation->donor_name), ' ') ?: $donation->donor_name;

        $message = sprintf(
            "Dear %s, thank you for your kind contribution of %s %s to the family. May God bless you. Ref: %s",
            $firstName,
            $donation->currency,
            $amountMajor,
            $reference
        );

        if ($this->sms->send($donation->phone, $message)) {
            $donation->update(['sms_sent' => true]);
        }
    }
}
