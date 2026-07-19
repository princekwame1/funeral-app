<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\SmsCampaign;
use App\Models\TenantEvent;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $stats = [
            'total_donations' => Donation::count(),
            'paid_amount_all' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount'),
            'paid_amount_today' => (int) Donation::where('status', Donation::STATUS_PAID)
                ->whereDate('paid_at', $today)
                ->sum('amount'),
            'paid_count_today' => Donation::where('status', Donation::STATUS_PAID)
                ->whereDate('paid_at', $today)
                ->count(),
            'pending' => Donation::where('status', Donation::STATUS_PENDING)->count(),
            'failed' => Donation::where('status', Donation::STATUS_FAILED)->count(),
            'sms_sent_total' => (int) SmsCampaign::sum('sent_count'),
            'sms_thankyou_sent' => Donation::where('sms_sent', true)->count(),
            'sms_campaigns' => SmsCampaign::count(),
        ];

        $trend = Donation::query()
            ->where('status', Donation::STATUS_PAID)
            ->where('paid_at', '>=', $sevenDaysAgo)
            ->get(['paid_at', 'amount'])
            ->groupBy(fn ($d) => Carbon::parse($d->paid_at)->format('Y-m-d'))
            ->map(fn ($rows) => (int) $rows->sum('amount'));

        $days = collect(range(0, 6))
            ->map(fn ($i) => Carbon::today()->subDays(6 - $i))
            ->map(fn ($d) => [
                'label' => $d->format('D'),
                'date' => $d->format('Y-m-d'),
                'amount' => (int) ($trend[$d->format('Y-m-d')] ?? 0),
            ]);

        $trendMax = max($days->max('amount'), 1);

        $recentDonations = Donation::query()
            ->with('user')
            ->latest()
            ->limit(6)
            ->get();

        $recentCampaigns = SmsCampaign::query()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        $breakdown = [
            'online' => Donation::where('payment_method', Donation::METHOD_ONLINE)
                ->where('status', Donation::STATUS_PAID)
                ->sum('amount'),
            'offline' => Donation::where('payment_method', Donation::METHOD_OFFLINE)
                ->where('status', Donation::STATUS_PAID)
                ->sum('amount'),
        ];

        $upcomingEvents = TenantEvent::query()
            ->where('starts_at', '>=', Carbon::now())
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'days', 'trendMax', 'recentDonations', 'recentCampaigns', 'breakdown', 'upcomingEvents'
        ));
    }
}
