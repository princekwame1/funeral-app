@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
    <div>
        <h2 style="margin: 0 0 4px;">Plans</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Subscription tiers, monthly SMS limits, donation caps and prices. Changes apply immediately across the platform.</p>
    </div>
    <button type="button" class="btn-primary" style="width: auto; padding: 10px 20px;" onclick="document.getElementById('newPlanCard').classList.toggle('open')">+ New plan</button>
</div>

@if (session('super_flash'))
    @php $sf = session('super_flash'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $sf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $sf['message'] }}</div>
    </div>
@endif

<div id="newPlanCard" class="card collapsible {{ old('name') ? 'open' : '' }}" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 12px; font-size: 15px;">Add a new plan</h3>
    <form method="POST" action="{{ route('super.plans.store') }}">
        @csrf
        @include('super.plans.form', ['plan' => null])
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">Create plan</button>
            <button type="button" class="btn-ghost" onclick="document.getElementById('newPlanCard').classList.remove('open')">Cancel</button>
        </div>
    </form>
</div>

<div class="plan-grid">
    @foreach ($plans as $plan)
        @php $tenantCount = (int) ($tenantCounts[$plan->slug] ?? 0); @endphp
        <div class="plan-card {{ $plan->is_active ? '' : 'inactive' }}">
            <div class="plan-header">
                <div>
                    <div class="plan-name">{{ $plan->name }}</div>
                    <div class="plan-slug">{{ $plan->slug }}</div>
                </div>
                <div class="plan-price">
                    @if ($plan->price_ghs > 0)
                        <div class="plan-price-value">GHS {{ number_format($plan->price_ghs) }}</div>
                        <div class="plan-price-tag">/ funeral</div>
                    @else
                        <div class="plan-price-value" style="color:#66bb6a;">Free</div>
                    @endif
                </div>
            </div>

            @if ($plan->tagline)
                <div class="plan-tagline">{{ $plan->tagline }}</div>
            @endif

            <div class="plan-limits">
                <div class="plan-limit">
                    <div class="limit-value">{{ $plan->sms_monthly === null ? 'Unlimited' : number_format($plan->sms_monthly) }}</div>
                    <div class="limit-label">SMS / month</div>
                </div>
                <div class="plan-limit">
                    <div class="limit-value">{{ $plan->donations_total === null ? 'Unlimited' : number_format($plan->donations_total) }}</div>
                    <div class="limit-label">Donations lifetime</div>
                </div>
                <div class="plan-limit">
                    <div class="limit-value">{{ number_format($tenantCount) }}</div>
                    <div class="limit-label">Tenants on plan</div>
                </div>
            </div>

            <div class="plan-status">
                @if ($plan->is_active)
                    <span class="badge badge-paid">Active</span>
                @else
                    <span class="badge badge-failed">Hidden</span>
                @endif
            </div>

            <div class="plan-actions">
                <button type="button" class="btn-verify" data-edit-plan="{{ $plan->id }}">Edit</button>
                <form method="POST" action="{{ route('super.plans.destroy', $plan) }}" style="margin: 0;"
                      data-confirm="Delete the {{ $plan->name }} plan? {{ $tenantCount > 0 ? 'Blocked: '.$tenantCount.' tenant(s) still on this plan.' : 'This cannot be undone.' }}"
                      data-confirm-title="Delete plan?"
                      data-confirm-icon="error"
                      data-confirm-text="Delete"
                      data-confirm-danger="1">
                    @csrf
                    <button type="submit" class="btn-verify" style="color: var(--red);" {{ $tenantCount > 0 ? 'disabled' : '' }} title="{{ $tenantCount > 0 ? 'Move tenants off this plan first' : 'Delete this plan' }}">Delete</button>
                </form>
            </div>

            <form method="POST" action="{{ route('super.plans.update', $plan) }}" class="plan-edit-form" id="edit-plan-{{ $plan->id }}">
                @csrf
                @include('super.plans.form', ['plan' => $plan])
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn-primary" style="width: auto; padding: 9px 20px;">Save changes</button>
                    <button type="button" class="btn-ghost" onclick="document.getElementById('edit-plan-{{ $plan->id }}').classList.remove('open')">Cancel</button>
                </div>
            </form>
        </div>
    @endforeach
</div>

<style>
    .collapsible { display: none; }
    .collapsible.open { display: block; }
    .plan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
    .plan-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; display: flex; flex-direction: column; gap: 14px; position: relative; }
    .plan-card.inactive { opacity: 0.6; }
    .plan-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .plan-name { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.3px; }
    .plan-slug { font-size: 11px; color: var(--text-dim); font-family: 'SF Mono', monospace; margin-top: 2px; }
    .plan-price { text-align: right; }
    .plan-price-value { font-size: 22px; font-weight: 700; color: var(--red); line-height: 1; }
    .plan-price-tag { font-size: 11px; color: var(--text-dim); margin-top: 4px; }
    .plan-tagline { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
    .plan-limits { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 12px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
    .plan-limit { text-align: center; }
    .limit-value { font-size: 16px; font-weight: 600; color: var(--text); }
    .limit-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }
    .plan-status { display: flex; }
    .plan-actions { display: flex; gap: 6px; }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 14px; border-radius: 999px; font-size: 12px; cursor: pointer; font-family: inherit; }
    .btn-verify:hover:not(:disabled) { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }
    .btn-verify:disabled { opacity: 0.4; cursor: not-allowed; }
    .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); padding: 9px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-ghost:hover { color: var(--text); border-color: var(--red); }
    .plan-edit-form { display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
    .plan-edit-form.open { display: block; }
</style>

<script>
    document.querySelectorAll('[data-edit-plan]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.editPlan;
            document.getElementById('edit-plan-' + id).classList.toggle('open');
        });
    });
</script>
@endsection
