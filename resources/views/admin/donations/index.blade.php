@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Donations</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">Take a new donation and review the full history in one place.</p>

<div class="stats" data-tour="donations-stats">
    <div class="stat">
        <div class="label">Total Donations</div>
        <div class="value">{{ number_format($totals['count']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Total Received (GHS)</div>
        <div class="value">{{ number_format($totals['paid_amount'] / 100, 2) }}</div>
    </div>
    <div class="stat">
        <div class="label">Pending</div>
        <div class="value">{{ number_format($totals['pending']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Failed</div>
        <div class="value">{{ number_format($totals['failed']) }}</div>
    </div>
</div>

@if (session('donation_result'))
    @php $dr = session('donation_result'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $dr['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 600; margin-bottom: 4px;">{{ $dr['ok'] ? 'Donation recorded' : 'Charge failed' }}</div>
        <div style="color: var(--text-muted); font-size: 14px;">{{ $dr['message'] }}</div>
    </div>
@endif

@can(\App\Support\Permissions::DONATIONS_CREATE)
<div class="card" data-tour="take-donation" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 4px; font-size: 16px;">Take a donation</h3>
    <p style="color: var(--text-muted); margin: 0 0 16px; font-size: 13px;">Fill donor details, choose Online (Paystack MoMo) or Manual (cash), then submit.</p>

    <form method="POST" action="{{ route('admin.donations.store') }}">
        @csrf
        <div class="donation-grid">
            <div class="form-group">
                <label for="donor_name" class="field-label">Donor name</label>
                <input type="text" name="donor_name" id="donor_name" value="{{ old('donor_name') }}" placeholder="Full name" required maxlength="120">
                @error('donor_name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="phone" class="field-label">Phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="0244123456" required maxlength="20" inputmode="tel">
                @error('phone')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="amount" class="field-label">Amount (GHS)</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" placeholder="50.00" required min="1" step="0.01">
                <div class="quick-amounts">
                    @foreach ([20, 50, 100, 200, 500] as $amt)
                        <button type="button" class="quick-amount-btn" data-amount="{{ $amt }}">{{ $amt }}</button>
                    @endforeach
                </div>
                @error('amount')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group" style="margin-top: 4px;">
            <div class="field-label" style="margin-bottom: 8px;">Payment method</div>
            <div class="method-options">
                <label class="method-option">
                    <input type="radio" name="payment_method" value="online" {{ old('payment_method', 'online') === 'online' ? 'checked' : '' }} onchange="document.getElementById('providerGroup').style.display=this.checked?'':'none';">
                    <div>
                        <div class="method-title">Online</div>
                        <div class="method-desc">Mobile money prompt via Paystack</div>
                    </div>
                </label>
                <label class="method-option">
                    <input type="radio" name="payment_method" value="offline" {{ old('payment_method') === 'offline' ? 'checked' : '' }} onchange="document.getElementById('providerGroup').style.display=this.checked?'none':'';">
                    <div>
                        <div class="method-title">Manual</div>
                        <div class="method-desc">Cash / already received — mark as paid</div>
                    </div>
                </label>
            </div>
            @error('payment_method')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group" id="providerGroup" style="{{ old('payment_method') === 'offline' ? 'display:none;' : '' }}">
            <label for="provider" class="field-label">Mobile money provider</label>
            <select name="provider" id="provider">
                @foreach ($providers as $key => $label)
                    <option value="{{ $key }}" {{ old('provider', $defaultProvider) === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('provider')<div class="error">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn-primary" style="width:auto; padding: 11px 28px;">Record donation</button>
    </form>
</div>
@endcan

<style>
    .donation-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .method-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
    .method-option { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 1px solid var(--border); background: var(--surface-2); border-radius: 8px; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
    .method-option:hover { border-color: rgba(var(--red-rgb),0.5); }
    .method-option input[type=radio] { margin-top: 3px; }
    .method-option:has(input:checked) { border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .method-title { font-size: 14px; font-weight: 500; color: var(--text); }
    .method-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    #providerGroup input, #providerGroup select, .donation-grid input, .donation-grid select { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    #providerGroup select:focus, .donation-grid input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
    .quick-amounts { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .quick-amount-btn { background: var(--surface-2); border: 1px solid var(--border); color: var(--text-muted); padding: 4px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
    .quick-amount-btn:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .quick-amount-btn.selected { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb),0.15); }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
    .btn-verify:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .export-group { display: flex; gap: 8px; align-items: center; }
    .btn-export { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: var(--surface); border: 1px solid var(--border); color: var(--text); border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; transition: border-color 0.15s, background 0.15s; }
    .btn-export:hover { border-color: var(--red); background: rgba(var(--red-rgb),0.06); }
    .export-icon { font-size: 10px; font-weight: 700; padding: 3px 6px; border-radius: 4px; letter-spacing: 0.4px; }
</style>

<script>
    (function () {
        var amt = document.getElementById('amount');
        document.querySelectorAll('.quick-amount-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!amt) return;
                amt.value = btn.dataset.amount;
                amt.focus();
                document.querySelectorAll('.quick-amount-btn').forEach(function (b) { b.classList.remove('selected'); });
                btn.classList.add('selected');
            });
        });
    })();
</script>

<h3 style="margin: 24px 0 12px; font-size: 15px; color: var(--text-muted); font-weight: 500;">Donation History</h3>

<div class="card">
    <div style="display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; margin-bottom: 16px;">
        <form method="GET" action="{{ route('admin.donations.index') }}" class="filters" style="margin: 0; flex: 1;" data-tour="donation-filters">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name, phone, reference...">
            <select name="status">
                <option value="">All statuses</option>
                <option value="paid" @selected($status === 'paid')>Paid</option>
                <option value="pending" @selected($status === 'pending')>Pending</option>
                <option value="failed" @selected($status === 'failed')>Failed</option>
            </select>
            <button type="submit">Filter</button>
        </form>
        <div class="export-group" data-tour="donation-exports">
            <span style="font-size: 12px; color: var(--text-dim); align-self: center;">Export:</span>
            <a href="{{ route('admin.donations.export.csv') }}?{{ http_build_query(['q' => $search, 'status' => $status]) }}" class="btn-export" title="Opens in Excel">
                <span class="export-icon" style="background: rgba(102,187,106,0.18); color: #66bb6a;">XLS</span>
                Excel
            </a>
            <a href="{{ route('admin.donations.export.pdf') }}?{{ http_build_query(['q' => $search, 'status' => $status]) }}" class="btn-export">
                <span class="export-icon" style="background: rgba(var(--red-rgb),0.18); color: var(--red);">PDF</span>
                PDF
            </a>
        </div>
    </div>

    @if ($donations->count() === 0)
        <div class="empty">No donations match your filter.</div>
    @else
        <table data-tour="donation-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Donor</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th>Thank-you SMS</th>
                    <th>Taken by</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($donations as $d)
                    <tr>
                        <td>{{ $d->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $d->donor_name }}</td>
                        <td>{{ $d->phone }}</td>
                        <td>{{ $d->currency }} {{ number_format($d->amount / 100, 2) }}</td>
                        <td><span class="badge badge-method-{{ $d->payment_method }}">{{ ucfirst($d->payment_method) }}</span></td>
                        <td><span class="badge badge-{{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
                        <td style="font-family: monospace; font-size: 12px;">{{ $d->paystack_reference ?? '—' }}</td>
                        <td>
                            @if ($d->sms_sent)
                                <span class="badge badge-paid">Sent</span>
                            @elseif ($d->status === 'paid')
                                <span class="badge badge-pending">Not sent</span>
                            @else
                                <span style="color: var(--text-dim);">—</span>
                            @endif
                        </td>
                        <td>{{ $d->user?->name ?? '—' }}</td>
                        <td>
                            @if ($d->payment_method === 'online' && $d->paystack_reference && in_array($d->status, ['pending', 'failed']) && auth()->user()->can(\App\Support\Permissions::DONATIONS_VERIFY))
                                <form method="POST" action="{{ route('admin.donations.verify', $d) }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-verify">Verify</button>
                                </form>
                            @else
                                <span style="color: var(--text-dim);">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $donations->links() }}</div>
    @endif
</div>

@php
    $hasPendingOnline = $donations->contains(fn ($d) => $d->payment_method === 'online' && $d->status === 'pending');
@endphp

@if ($hasPendingOnline)
<div id="autoVerifyIndicator" style="position: fixed; bottom: 16px; right: 16px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text-muted); font-size: 12px; padding: 8px 12px; border-radius: 999px; display: flex; align-items: center; gap: 8px; z-index: 20;">
    <span class="btn-spinner" style="width: 12px; height: 12px; border-color: rgba(255,255,255,0.2); border-top-color: var(--red);"></span>
    <span>Auto-verifying pending charges…</span>
</div>
<script>
    (function () {
        var url = @json(route('admin.donations.auto-verify'));
        var csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
        var interval = 8000;
        var maxAttempts = 45; // ~6 minutes total

        function tick(attempt) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.changed > 0) {
                    window.location.reload();
                    return;
                }
                if (attempt + 1 < maxAttempts) {
                    setTimeout(function () { tick(attempt + 1); }, interval);
                } else {
                    var el = document.getElementById('autoVerifyIndicator');
                    if (el) el.style.display = 'none';
                }
            })
            .catch(function () {
                if (attempt + 1 < maxAttempts) {
                    setTimeout(function () { tick(attempt + 1); }, interval);
                }
            });
        }

        // First tick after a short delay so the page settles
        setTimeout(function () { tick(0); }, 4000);
    })();
</script>
@endif

<script>
    window.__tourKey = 'donations-v1';
    window.__tourSteps = [
        {
            target: '[data-tour="donations-stats"]',
            position: 'bottom',
            title: 'Donation totals',
            body: 'Quick summary — how many donations exist and how much has been received so far. Filtering the table below does not change these totals.',
        },
        @can(\App\Support\Permissions::DONATIONS_CREATE)
        {
            target: '[data-tour="take-donation"]',
            position: 'bottom',
            title: 'Record a donation',
            body: 'Choose <strong>Online</strong> to send a Paystack MoMo prompt to the donor\'s phone, or <strong>Manual</strong> to record cash already received. Both methods trigger a thank-you SMS.',
        },
        @endcan
        {
            target: '[data-tour="donation-filters"]',
            position: 'bottom',
            title: 'Search & filter',
            body: 'Search by donor name, phone number, or Paystack reference. Filter by status to isolate paid, pending, or failed donations.',
        },
        {
            target: '[data-tour="donation-exports"]',
            position: 'left',
            title: 'Export the current view',
            body: 'Download the <strong>filtered</strong> list as Excel (CSV) or PDF — perfect for reports, sharing with family, or filing.',
        },
        @if ($donations->count() > 0)
        {
            target: '[data-tour="donation-table"]',
            position: 'top',
            title: 'Donation history',
            body: 'Every donation with donor details, method, status, thank-you SMS status, and who recorded it. Pending online charges get a <strong>Verify</strong> button to check with Paystack manually.',
        },
        @endif
    ];
</script>

@endsection
