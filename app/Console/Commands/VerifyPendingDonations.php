<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Services\DonationConfirmer;
use App\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class VerifyPendingDonations extends Command
{
    protected $signature = 'donations:verify-pending {--minutes=60 : How far back to look for pending online donations}';

    protected $description = 'Verify pending online donations against Paystack and flip statuses to paid or failed.';

    public function handle(PaystackService $paystack, DonationConfirmer $confirmer): int
    {
        $cutoff = Carbon::now()->subMinutes((int) $this->option('minutes'));

        $pending = Donation::query()
            ->where('payment_method', Donation::METHOD_ONLINE)
            ->where('status', Donation::STATUS_PENDING)
            ->whereNotNull('paystack_reference')
            ->where('created_at', '>=', $cutoff)
            ->orderBy('created_at')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending online donations to verify.');
            return self::SUCCESS;
        }

        $paid = 0;
        $failed = 0;

        foreach ($pending as $donation) {
            $result = $paystack->verify($donation->paystack_reference);
            $status = data_get($result, 'body.data.status');
            $gatewayMessage = data_get($result, 'body.data.gateway_response')
                ?? data_get($result, 'body.data.message');

            if ($status === 'success') {
                $confirmer->markPaid($donation, $result['body']);
                $paid++;
                $this->line("  #{$donation->id} → paid");
            } elseif (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                $donation->update([
                    'status' => Donation::STATUS_FAILED,
                    'gateway_response' => $gatewayMessage ?? $donation->gateway_response,
                ]);
                $failed++;
                $this->line("  #{$donation->id} → failed ({$status})");
            }
        }

        $this->info("Checked {$pending->count()}. Paid: {$paid}. Failed: {$failed}.");

        return self::SUCCESS;
    }
}
