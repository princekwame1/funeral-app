<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\DonationConfirmer;
use App\Services\PaystackService;
use App\Support\CurrentTenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDonationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');

        $query = Donation::query()->with('user')->latest();

        if ($status && in_array($status, ['pending', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('donor_name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('paystack_reference', 'like', "%$search%");
            });
        }

        $donations = $query->paginate(25)->withQueryString();

        $totals = [
            'count' => Donation::count(),
            'paid_amount' => Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'pending' => Donation::where('status', Donation::STATUS_PENDING)->count(),
            'failed' => Donation::where('status', Donation::STATUS_FAILED)->count(),
        ];

        $providers = [
            'mtn' => 'MTN Mobile Money',
            'vod' => 'Vodafone Cash',
            'tgo' => 'AirtelTigo Money',
            'atl' => 'AT Money',
        ];

        $defaultProvider = (string) config('services.paystack.default_provider', 'mtn');

        return view('admin.donations.index', compact(
            'donations', 'totals', 'status', 'search', 'providers', 'defaultProvider'
        ));
    }

    public function store(Request $request, PaystackService $paystack, DonationConfirmer $confirmer, CurrentTenant $current)
    {
        $tenant = $current->get();
        if ($tenant && ! \App\Support\Plans::canRecordDonation($tenant)) {
            $limit = \App\Support\Plans::limits($tenant)['donations_total'];
            return redirect()->route('admin.donations.index')
                ->with('donation_result', [
                    'ok' => false,
                    'message' => "Plan limit reached: {$limit} lifetime donations. Upgrade the tenant's plan to continue.",
                ]);
        }

        $data = $request->validate([
            'donor_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:online,offline'],
            'provider' => ['nullable', 'string', 'in:mtn,vod,tgo,atl'],
        ]);

        $amountMinor = (int) round(((float) $data['amount']) * 100);
        $currency = (string) config('services.paystack.default_currency', 'GHS');
        $user = $request->user();

        if ($data['payment_method'] === Donation::METHOD_OFFLINE) {
            $donation = Donation::create([
                'user_id' => $user->id,
                'donor_name' => $data['donor_name'],
                'phone' => $data['phone'],
                'amount' => $amountMinor,
                'currency' => $currency,
                'payment_method' => Donation::METHOD_OFFLINE,
                'status' => Donation::STATUS_PAID,
                'paystack_channel' => 'cash',
                'gateway_response' => 'Recorded offline by ' . $user->name,
                'paid_at' => Carbon::now(),
            ]);

            $confirmer->sendThankYou($donation);

            return redirect()
                ->route('admin.donations.index')
                ->with('donation_result', [
                    'ok' => true,
                    'message' => "Offline donation of {$currency} " . number_format($amountMinor / 100, 2) . " recorded for {$donation->donor_name}.",
                    'donation_id' => $donation->id,
                ]);
        }

        $provider = $data['provider'] ?? (string) config('services.paystack.default_provider', 'mtn');

        $charge = $paystack->chargeMobileMoney(
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
                ?? data_get($charge, 'body.data.message')
                ?? data_get($charge, 'body.data.gateway_response')
                ?? data_get($charge, 'body.message'),
        ]);

        if ($charge['ok']) {
            $message = data_get($charge, 'body.data.display_text')
                ?? "Charge initiated. Donor should approve the prompt on their phone. Reference: {$charge['reference']}.";
            return redirect()
                ->route('admin.donations.index')
                ->with('donation_result', [
                    'ok' => true,
                    'message' => $message,
                    'donation_id' => $donation->id,
                ]);
        }

        $reason = data_get($charge, 'body.data.message')
            ?? data_get($charge, 'body.data.gateway_response')
            ?? data_get($charge, 'body.message', 'Paystack rejected the charge.');

        return redirect()
            ->route('admin.donations.index')
            ->withInput()
            ->with('donation_result', [
                'ok' => false,
                'message' => 'Paystack declined: ' . $reason,
                'donation_id' => $donation->id,
            ]);
    }

    public function autoVerify(PaystackService $paystack, DonationConfirmer $confirmer)
    {
        $pending = Donation::query()
            ->where('payment_method', Donation::METHOD_ONLINE)
            ->where('status', Donation::STATUS_PENDING)
            ->whereNotNull('paystack_reference')
            ->where('created_at', '>=', Carbon::now()->subMinutes(60))
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        $changes = [];

        foreach ($pending as $donation) {
            $result = $paystack->verify($donation->paystack_reference);
            $status = data_get($result, 'body.data.status');
            $gatewayMessage = data_get($result, 'body.data.gateway_response')
                ?? data_get($result, 'body.data.message');

            if ($status === 'success') {
                $confirmer->markPaid($donation, $result['body']);
                $changes[] = ['id' => $donation->id, 'to' => Donation::STATUS_PAID];
            } elseif (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                $donation->update([
                    'status' => Donation::STATUS_FAILED,
                    'gateway_response' => $gatewayMessage ?? $donation->gateway_response,
                ]);
                $changes[] = ['id' => $donation->id, 'to' => Donation::STATUS_FAILED];
            }
        }

        return response()->json([
            'checked' => $pending->count(),
            'changed' => count($changes),
            'changes' => $changes,
        ]);
    }

    public function verify(Donation $donation, PaystackService $paystack, DonationConfirmer $confirmer)
    {
        if (! $donation->paystack_reference) {
            return redirect()
                ->route('admin.donations.index')
                ->with('donation_result', [
                    'ok' => false,
                    'message' => "Donation #{$donation->id} has no Paystack reference to verify.",
                ]);
        }

        $result = $paystack->verify($donation->paystack_reference);
        $status = data_get($result, 'body.data.status');
        $gatewayMessage = data_get($result, 'body.data.gateway_response')
            ?? data_get($result, 'body.data.message')
            ?? data_get($result, 'body.message');

        if ($status === 'success') {
            if ($donation->status !== Donation::STATUS_PAID) {
                $confirmer->markPaid($donation, $result['body']);
            }

            return redirect()
                ->route('admin.donations.index')
                ->with('donation_result', [
                    'ok' => true,
                    'message' => "Verified: {$donation->donor_name}'s donation is paid. Thank-you SMS sent.",
                    'donation_id' => $donation->id,
                ]);
        }

        if (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
            $donation->update([
                'status' => Donation::STATUS_FAILED,
                'gateway_response' => $gatewayMessage ?? $donation->gateway_response,
            ]);

            return redirect()
                ->route('admin.donations.index')
                ->with('donation_result', [
                    'ok' => false,
                    'message' => "Paystack says: {$status}" . ($gatewayMessage ? " — {$gatewayMessage}" : ''),
                    'donation_id' => $donation->id,
                ]);
        }

        return redirect()
            ->route('admin.donations.index')
            ->with('donation_result', [
                'ok' => true,
                'message' => "Still {$status}. Donor may not have approved the MoMo prompt yet — retry in a moment.",
                'donation_id' => $donation->id,
            ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $donations = $this->filteredQuery($request)->cursor();
        $filename = 'donations-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($donations) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Date', 'Donor', 'Phone', 'Amount', 'Currency', 'Method',
                'Status', 'Reference', 'Channel', 'Thank-you SMS', 'Taken by',
            ]);

            foreach ($donations as $d) {
                fputcsv($out, [
                    $d->created_at?->format('Y-m-d H:i'),
                    $d->donor_name,
                    $d->phone,
                    number_format($d->amount / 100, 2, '.', ''),
                    $d->currency,
                    ucfirst($d->payment_method),
                    ucfirst($d->status),
                    $d->paystack_reference,
                    $d->paystack_channel,
                    $d->sms_sent ? 'Sent' : ($d->status === 'paid' ? 'Not sent' : ''),
                    $d->user?->name,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function exportPdf(Request $request, CurrentTenant $current)
    {
        $donations = $this->filteredQuery($request)->limit(2000)->get();

        $totals = [
            'count' => $donations->count(),
            'paid_amount' => (int) $donations->where('status', Donation::STATUS_PAID)->sum('amount'),
            'pending' => $donations->where('status', Donation::STATUS_PENDING)->count(),
            'failed' => $donations->where('status', Donation::STATUS_FAILED)->count(),
        ];

        $tenant = $current->get();
        $filters = [
            'status' => $request->query('status'),
            'search' => $request->query('q'),
        ];

        $pdf = Pdf::loadView('admin.donations.export-pdf', compact('donations', 'totals', 'tenant', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('donations-' . now()->format('Y-m-d-His') . '.pdf');
    }

    private function filteredQuery(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');

        $query = Donation::query()->with('user')->latest();

        if ($status && in_array($status, ['pending', 'paid', 'failed'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('donor_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('paystack_reference', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
