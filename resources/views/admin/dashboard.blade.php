@extends('layouts.app')

@section('content')
@php
    $splash = app(\App\Support\CurrentTenant::class)->get()?->splash_image_url;
@endphp

@if ($splash)
<div class="hero-banner" style="background-image: linear-gradient(to right, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.5) 60%, rgba(0,0,0,0.15) 100%), url('{{ $splash }}');">
    <div>
        <h2 style="margin: 0 0 6px; font-size: 26px;">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h2>
        <p style="color: var(--text-muted); margin: 0 0 14px; font-size: 14px;">Here's what's happening today.</p>
        <div style="display:flex; gap: 8px; flex-wrap: wrap;">
            @can(\App\Support\Permissions::DONATIONS_CREATE)
                <a href="{{ route('admin.donations.index') }}" class="btn-primary" style="width:auto; padding: 10px 20px; text-decoration: none; display: inline-block;">+ Take donation</a>
            @endcan
            @can(\App\Support\Permissions::SMS_NOTIFICATIONS_VIEW)
                <a href="{{ route('admin.sms.notifications') }}" class="btn-outline">Send SMS</a>
            @endcan
        </div>
    </div>
</div>
@else
<div style="display:flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
    <div>
        <h2 style="margin: 0 0 4px;">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Here's what's happening with the funeral donations today.</p>
    </div>
    <div style="display:flex; gap: 8px; flex-wrap: wrap;">
        @can(\App\Support\Permissions::DONATIONS_CREATE)
            <a href="{{ route('admin.donations.index') }}" class="btn-primary" style="width:auto; padding: 10px 20px; text-decoration: none; display: inline-block;">+ Take donation</a>
        @endcan
        @can(\App\Support\Permissions::SMS_NOTIFICATIONS_VIEW)
            <a href="{{ route('admin.sms.notifications') }}" class="btn-outline">Send SMS</a>
        @endcan
    </div>
</div>
@endif

<div class="stats" data-tour="stats" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat">
        <div class="label">Received Today (GHS)</div>
        <div class="value">{{ number_format($stats['paid_amount_today'] / 100, 2) }}</div>
        <div class="stat-hint">{{ $stats['paid_count_today'] }} donation(s) today</div>
    </div>
    <div class="stat">
        <div class="label">Total Received (GHS)</div>
        <div class="value">{{ number_format($stats['paid_amount_all'] / 100, 2) }}</div>
        <div class="stat-hint">{{ number_format($stats['total_donations']) }} lifetime</div>
    </div>
    <div class="stat">
        <div class="label">Pending</div>
        <div class="value">{{ number_format($stats['pending']) }}</div>
        <div class="stat-hint">Awaiting donor approval</div>
    </div>
    <div class="stat">
        <div class="label">Failed</div>
        <div class="value">{{ number_format($stats['failed']) }}</div>
        <div class="stat-hint">Declined / abandoned</div>
    </div>
    <div class="stat">
        <div class="label">SMS Sent (bulk)</div>
        <div class="value">{{ number_format($stats['sms_sent_total']) }}</div>
        <div class="stat-hint">{{ number_format($stats['sms_campaigns']) }} campaigns</div>
    </div>
    <div class="stat">
        <div class="label">Thank-you SMS</div>
        <div class="value">{{ number_format($stats['sms_thankyou_sent']) }}</div>
        <div class="stat-hint">Delivered to donors</div>
    </div>
</div>

<div class="dashboard-grid" data-tour="charts">
    <div class="card">
        <div class="card-header">
            <h3 style="margin:0; font-size: 15px;">Last 7 days</h3>
            <span style="font-size: 12px; color: var(--text-dim);">Paid donations</span>
        </div>
        <div class="trend-chart">
            @foreach ($days as $day)
                <div class="trend-col" title="{{ $day['date'] }}">
                    <div class="trend-bar-wrap">
                        <div class="trend-bar" style="height: {{ $day['amount'] > 0 ? max(4, ($day['amount'] / $trendMax) * 100) : 0 }}%;"></div>
                    </div>
                    <div class="trend-amt">{{ $day['amount'] > 0 ? number_format($day['amount'] / 100) : '—' }}</div>
                    <div class="trend-label">{{ $day['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 style="margin:0; font-size: 15px;">Payment mix</h3>
            <span style="font-size: 12px; color: var(--text-dim);">All time paid</span>
        </div>
        @php
            $totalMix = $breakdown['online'] + $breakdown['offline'];
            $onlinePct = $totalMix > 0 ? round(($breakdown['online'] / $totalMix) * 100) : 0;
            $offlinePct = $totalMix > 0 ? 100 - $onlinePct : 0;
        @endphp
        <div class="mix-bar">
            <div class="mix-online" style="width: {{ $onlinePct }}%;"></div>
            <div class="mix-offline" style="width: {{ $offlinePct }}%;"></div>
        </div>
        <div class="mix-legend">
            <div>
                <div class="mix-dot mix-online-dot"></div>
                <div>
                    <div class="mix-key">Online</div>
                    <div class="mix-val">GHS {{ number_format($breakdown['online'] / 100, 2) }} · {{ $onlinePct }}%</div>
                </div>
            </div>
            <div>
                <div class="mix-dot mix-offline-dot"></div>
                <div>
                    <div class="mix-key">Manual (cash)</div>
                    <div class="mix-val">GHS {{ number_format($breakdown['offline'] / 100, 2) }} · {{ $offlinePct }}%</div>
                </div>
            </div>
        </div>
    </div>
</div>

@can(\App\Support\Permissions::EVENTS_VIEW)
    @if ($upcomingEvents->count())
        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <h3 style="margin:0; font-size: 15px;">Upcoming events</h3>
                <a href="{{ route('admin.events.index') }}" class="see-all">See all →</a>
            </div>
            <div class="events-mini">
                @foreach ($upcomingEvents as $e)
                    <div class="events-mini-row">
                        <div class="events-mini-date">
                            <div class="day">{{ $e->starts_at->format('d') }}</div>
                            <div class="month">{{ $e->starts_at->format('M') }}</div>
                        </div>
                        <div style="min-width: 0; flex: 1;">
                            <div class="events-mini-title">{{ $e->title }}</div>
                            <div class="events-mini-meta">
                                {{ $e->starts_at->format('D, H:i') }}
                                @if ($e->venue) · {{ $e->venue }} @endif
                            </div>
                        </div>
                        <div class="events-mini-when">{{ $e->starts_at->diffForHumans() }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endcan

<div class="dashboard-grid" data-tour="recent" style="margin-top: 16px;">
    <div class="card">
        <div class="card-header">
            <h3 style="margin:0; font-size: 15px;">Recent donations</h3>
            <a href="{{ route('admin.donations.index') }}" class="see-all">See all →</a>
        </div>
        @if ($recentDonations->isEmpty())
            <div class="empty" style="padding: 20px;">No donations yet.</div>
        @else
            <div class="recent-list">
                @foreach ($recentDonations as $d)
                    <div class="recent-row">
                        <div>
                            <div class="recent-title">{{ $d->donor_name }}</div>
                            <div class="recent-sub">{{ $d->created_at->diffForHumans() }} · {{ ucfirst($d->payment_method) }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="recent-amount">{{ $d->currency }} {{ number_format($d->amount / 100, 2) }}</div>
                            <span class="badge badge-{{ $d->status }}">{{ ucfirst($d->status) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h3 style="margin:0; font-size: 15px;">Recent SMS campaigns</h3>
            <a href="{{ route('admin.sms.logs') }}" class="see-all">See all →</a>
        </div>
        @if ($recentCampaigns->isEmpty())
            <div class="empty" style="padding: 20px;">No campaigns yet.</div>
        @else
            <div class="recent-list">
                @foreach ($recentCampaigns as $c)
                    <div class="recent-row">
                        <div style="min-width: 0; flex: 1;">
                            <div class="recent-title" style="overflow:hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $c->campaign_name ?? 'Untitled campaign' }}</div>
                            <div class="recent-sub">{{ $c->created_at->diffForHumans() }} · Scope: {{ ucfirst($c->scope ?? '—') }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="recent-amount">{{ number_format($c->sent_count) }} sent</div>
                            <span class="badge badge-{{ $c->status === 'sent' ? 'paid' : ($c->status === 'failed' ? 'failed' : 'pending') }}">{{ ucfirst($c->status) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    .stat .stat-hint { font-size: 11px; color: var(--text-dim); margin-top: 4px; letter-spacing: 0.3px; }
    .btn-outline { padding: 10px 20px; background: transparent; color: var(--text); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; transition: border-color 0.15s, background 0.15s; }
    .btn-outline:hover { border-color: var(--red); background: rgba(var(--red-rgb),0.08); }

    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .see-all { font-size: 12px; color: var(--red); text-decoration: none; }
    .see-all:hover { text-decoration: underline; }

    .trend-chart { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; align-items: end; height: 200px; }
    .trend-col { display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; }
    .trend-bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; }
    .trend-bar { width: 100%; background: linear-gradient(180deg, var(--red), var(--dark-red)); border-radius: 4px 4px 0 0; min-height: 0; transition: opacity 0.15s; }
    .trend-bar:hover { opacity: 0.85; }
    .trend-amt { font-size: 11px; color: var(--text-muted); }
    .trend-label { font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; }

    .mix-bar { display: flex; height: 12px; border-radius: 999px; overflow: hidden; background: var(--surface-2); }
    .mix-online { background: #64b5f6; }
    .mix-offline { background: var(--red); }
    .mix-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
    .mix-legend > div { display: flex; align-items: center; gap: 10px; }
    .mix-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .mix-online-dot { background: #64b5f6; }
    .mix-offline-dot { background: var(--red); }
    .mix-key { font-size: 12px; color: var(--text-muted); }
    .mix-val { font-size: 14px; color: var(--text); font-weight: 500; }

    .recent-list { display: flex; flex-direction: column; gap: 12px; }
    .recent-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; }
    .recent-title { color: var(--text); font-weight: 500; font-size: 14px; }
    .recent-sub { color: var(--text-dim); font-size: 12px; margin-top: 2px; }
    .recent-amount { color: var(--text); font-weight: 500; font-size: 14px; margin-bottom: 4px; }

    .hero-banner { background-size: cover; background-position: center; border-radius: 12px; padding: 40px 32px; margin-bottom: 20px; border: 1px solid var(--border); min-height: 200px; display: flex; align-items: flex-end; }
    .hero-banner h2 { color: var(--text); }

    .events-mini { display: flex; flex-direction: column; gap: 10px; }
    .events-mini-row { display: flex; align-items: center; gap: 14px; padding: 10px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; }
    .events-mini-date { width: 48px; text-align: center; }
    .events-mini-date .day { font-size: 18px; font-weight: 700; color: var(--red); line-height: 1; }
    .events-mini-date .month { font-size: 10px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-top: 2px; }
    .events-mini-title { font-size: 14px; font-weight: 500; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .events-mini-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .events-mini-when { font-size: 11px; color: var(--text-dim); flex-shrink: 0; }
</style>

<script>
    window.__tourKey = 'dashboard-v1';
    window.__tourSteps = [
        {
            target: '[data-tour="sidebar"]',
            position: 'right',
            title: 'Your sidebar',
            body: 'Everything you need is one click away — Dashboard, Donations, and SMS Notifications. Super admins also see Tenants, Branding, Users, and Roles here.',
        },
        {
            target: '[data-tour="stats"]',
            position: 'bottom',
            title: 'At-a-glance numbers',
            body: 'Money received today, running totals, pending and failed counts — plus how many SMS you\'ve sent. Refreshes every time you visit.',
        },
        {
            target: '[data-tour="charts"]',
            position: 'top',
            title: 'Trends and payment mix',
            body: 'The 7-day chart shows paid donations per day. The payment mix bar breaks down how much came in via mobile money vs cash.',
        },
        {
            target: '[data-tour="recent"]',
            position: 'top',
            title: 'Recent activity',
            body: 'The last few donations and SMS campaigns. Click "See all →" to jump to the full history.',
        },
        {
            target: '[data-tour="profile"]',
            position: 'left',
            title: 'Your profile menu',
            body: 'Sign out or replay this tour anytime from here. You can also skip a tour with the Esc key.',
        },
    ];
</script>
@endsection
