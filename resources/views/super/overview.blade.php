@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Super Admin</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">Cross-tenant overview. All numbers below span every tenant on the platform.</p>

@if (session('super_flash'))
    @php $sf = session('super_flash'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $sf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $sf['message'] }}</div>
    </div>
@endif

<div class="stats">
    <div class="stat">
        <div class="label">Tenants</div>
        <div class="value">{{ number_format($stats['tenants']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Admins & Supers</div>
        <div class="value">{{ number_format($stats['admins']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Total Donations</div>
        <div class="value">{{ number_format($stats['donations']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Total Received (GHS)</div>
        <div class="value">{{ number_format($stats['paid_amount'] / 100, 2) }}</div>
    </div>
    <div class="stat">
        <div class="label">SMS Campaigns</div>
        <div class="value">{{ number_format($stats['sms_campaigns']) }}</div>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0; font-size: 15px;">All Tenants</h3>
        <a href="{{ route('super.tenants.create') }}" class="btn-primary" style="width:auto; padding: 8px 16px; text-decoration: none; display: inline-block;">+ Add tenant</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Users</th>
                <th>Brand</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tenants as $t)
                <tr>
                    <td style="font-weight: 500;">{{ $t->name }}</td>
                    <td style="color: var(--text-muted); font-family: monospace; font-size: 12px;">{{ $t->slug }}</td>
                    <td>{{ $t->users_count }}</td>
                    <td>
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: {{ $t->brand_primary }}; vertical-align: -2px; border: 1px solid var(--border);"></span>
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: {{ $t->brand_accent }}; vertical-align: -2px; border: 1px solid var(--border);"></span>
                    </td>
                    <td>
                        @if ($t->is_active)
                            <span class="badge badge-paid">Active</span>
                        @else
                            <span class="badge badge-failed">Disabled</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('super.tenants.switch', $t) }}" style="display: inline; margin: 0;">
                            @csrf
                            <button type="submit" class="btn-verify">Enter</button>
                        </form>
                        <a href="{{ route('super.tenants.edit', $t) }}" class="btn-verify" style="text-decoration: none; display: inline-block; margin-left: 4px;">Edit</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
    .btn-verify:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
</style>
@endsection
