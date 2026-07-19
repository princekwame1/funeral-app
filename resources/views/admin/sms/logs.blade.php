@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">SMS Logs</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">History of every bulk campaign and thank-you SMS the system has processed.</p>

<div class="stats">
    <div class="stat">
        <div class="label">Total Campaigns</div>
        <div class="value">{{ number_format($totals['campaigns']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Total SMS Sent</div>
        <div class="value">{{ number_format($totals['total_sent']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Total Failed</div>
        <div class="value">{{ number_format($totals['total_failed']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Thank-you SMS Sent</div>
        <div class="value">{{ number_format($totals['thank_you_sent']) }}</div>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('admin.sms.logs') }}" class="filters">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search name, message, campaign ID...">
        <select name="status">
            <option value="">All statuses</option>
            <option value="sent" @selected($status === 'sent')>Sent</option>
            <option value="partial" @selected($status === 'partial')>Partial</option>
            <option value="failed" @selected($status === 'failed')>Failed</option>
            <option value="empty" @selected($status === 'empty')>Empty</option>
        </select>
        <button type="submit">Filter</button>
    </form>

    @if ($campaigns->count() === 0)
        <div class="empty">No campaigns match your filter.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Sent</th>
                    <th>Name</th>
                    <th>Scope</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Failed</th>
                    <th>Skipped</th>
                    <th>Status</th>
                    <th>Sender ID</th>
                    <th>Sent by</th>
                    <th>Provider ID</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($campaigns as $c)
                    <tr>
                        <td>{{ $c->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $c->campaign_name ?? '—' }}</td>
                        <td><span class="badge badge-method-offline">{{ ucfirst($c->scope ?? '—') }}</span></td>
                        <td>{{ number_format($c->recipient_count) }}</td>
                        <td style="color:#66bb6a; font-weight: 500;">{{ number_format($c->sent_count) }}</td>
                        <td style="color:{{ $c->failed_count > 0 ? 'var(--red)' : 'var(--text-dim)' }};">{{ number_format($c->failed_count) }}</td>
                        <td style="color: var(--text-dim);">{{ number_format($c->skipped_count) }}</td>
                        <td><span class="badge badge-{{ $c->status === 'sent' ? 'paid' : ($c->status === 'failed' ? 'failed' : 'pending') }}">{{ ucfirst($c->status) }}</span></td>
                        <td style="color: var(--text-muted);">{{ $c->sender_id ?? '—' }}</td>
                        <td>{{ $c->user?->name ?? '—' }}</td>
                        <td style="font-family: monospace; font-size: 12px; color: var(--text-dim);">{{ $c->provider_campaign_id ?? '—' }}</td>
                        <td style="max-width: 320px; color: var(--text-muted); font-size: 13px;">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $c->message }}">{{ $c->message }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
