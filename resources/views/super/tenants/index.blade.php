@extends('layouts.app')

@section('content')
<div style="display:flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
    <div>
        <h2 style="margin: 0 0 4px;">Tenants</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Every family / funeral tenant on the platform.</p>
    </div>
    <a href="{{ route('super.tenants.create') }}" class="btn-primary" style="width:auto; padding: 10px 20px; text-decoration: none; display: inline-block;">+ Add tenant</a>
</div>

@if (session('super_flash'))
    @php $sf = session('super_flash'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $sf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $sf['message'] }}</div>
    </div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Plan</th>
                <th>Usage this month</th>
                <th>Users</th>
                <th>Donations</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tenants as $t)
                @php
                    $limits = \App\Support\Plans::limits($t);
                    $usage = \App\Support\Plans::usage($t);
                    $planDef = \App\Support\Plans::definition($t->plan);
                    $smsRatio = $limits['sms_monthly'] ? min(1, $usage['sms_monthly'] / $limits['sms_monthly']) : 0;
                    $donRatio = $limits['donations_total'] ? min(1, $usage['donations_total'] / $limits['donations_total']) : 0;
                @endphp
                <tr>
                    <td>
                        <div style="font-weight: 500;">{{ $t->name }}</div>
                        <div style="font-size: 12px; color: var(--text-dim); margin-top: 2px;">
                            <a href="{{ tenant_public_url($t) }}" target="_blank" rel="noopener" style="color: var(--text-dim); text-decoration: none;">{{ tenant_public_host($t) }} &#8599;</a>
                        </div>
                    </td>
                    <td style="color: var(--text-muted); font-family: monospace; font-size: 12px;">{{ $t->slug }}</td>
                    <td><span class="badge {{ $t->plan === 'pro' ? 'badge-paid' : ($t->plan === 'starter' ? 'badge-pending' : 'badge-method-offline') }}">{{ $planDef['name'] }}</span></td>
                    <td style="min-width: 180px;">
                        <div class="usage-mini">
                            <div class="usage-mini-label">SMS <strong>{{ number_format($usage['sms_monthly']) }}</strong>{{ $limits['sms_monthly'] !== null ? ' / ' . number_format($limits['sms_monthly']) : '' }}</div>
                            @if ($limits['sms_monthly'] !== null)
                                <div class="usage-bar"><span style="width: {{ round($smsRatio * 100) }}%;{{ $smsRatio >= 0.9 ? 'background: var(--red);' : '' }}"></span></div>
                            @endif
                            <div class="usage-mini-label" style="margin-top: 4px;">Donations <strong>{{ number_format($usage['donations_total']) }}</strong>{{ $limits['donations_total'] !== null ? ' / ' . number_format($limits['donations_total']) : '' }}</div>
                            @if ($limits['donations_total'] !== null)
                                <div class="usage-bar"><span style="width: {{ round($donRatio * 100) }}%;{{ $donRatio >= 0.9 ? 'background: var(--red);' : '' }}"></span></div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $t->users_count }}</td>
                    <td>{{ $t->donations_count }}</td>
                    <td>
                        @if ($t->isArchived())
                            <span class="badge badge-failed" title="Archived on {{ $t->archived_at->format('d M Y') }}">Archived</span>
                        @elseif ($t->archive_at)
                            <span class="badge badge-pending" title="Auto-archives on {{ $t->archive_at->format('d M Y') }}">Archives {{ $t->archive_at->diffForHumans() }}</span>
                        @elseif ($t->is_active)
                            <span class="badge badge-paid">Active</span>
                        @else
                            <span class="badge badge-failed">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap: 4px; flex-wrap: wrap;">
                            <form method="POST" action="{{ route('super.tenants.switch', $t) }}" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn-verify">Enter</button>
                            </form>
                            <a href="{{ route('super.tenants.edit', $t) }}" class="btn-verify" style="text-decoration: none; display: inline-block;">Edit</a>
                            @if ($t->isArchived())
                                <form method="POST" action="{{ route('super.tenants.unarchive', $t) }}" style="margin: 0;"
                                      data-confirm="Bring {{ $t->name }} out of archive? Writes will be allowed again."
                                      data-confirm-title="Unarchive tenant?"
                                      data-confirm-icon="info"
                                      data-confirm-text="Unarchive">
                                    @csrf
                                    <button type="submit" class="btn-verify">Unarchive</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('super.tenants.archive', $t) }}" style="margin: 0;"
                                      data-confirm="Archive {{ $t->name }} now? The tenant will become read-only immediately."
                                      data-confirm-title="Archive tenant?"
                                      data-confirm-icon="warning"
                                      data-confirm-text="Archive"
                                      data-confirm-danger="1">
                                    @csrf
                                    <button type="submit" class="btn-verify">Archive</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('super.tenants.destroy', $t) }}" style="margin: 0;"
                                  data-confirm="Delete tenant &quot;{{ $t->name }}&quot;? This does not delete their data."
                                  data-confirm-title="Delete tenant?"
                                  data-confirm-icon="error"
                                  data-confirm-text="Delete"
                                  data-confirm-danger="1">
                                @csrf
                                <button type="submit" class="btn-verify" style="color: var(--red);">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $tenants->links() }}</div>
</div>

<style>
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
    .btn-verify:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .usage-mini { font-size: 11px; color: var(--text-muted); }
    .usage-mini-label strong { color: var(--text); }
    .usage-bar { height: 3px; background: rgba(255,255,255,0.06); border-radius: 3px; overflow: hidden; margin-top: 2px; }
    .usage-bar span { display: block; height: 100%; background: #66bb6a; transition: width 0.15s; }
</style>
@endsection
