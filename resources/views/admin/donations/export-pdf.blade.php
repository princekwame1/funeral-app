<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Donations Export</title>
    <style>
        @page { margin: 32px 28px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10px; color: #222; margin: 0; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid {{ $tenant?->brand_primary ?? '#D32F2F' }}; padding-bottom: 12px; }
        .brand { font-size: 16px; font-weight: 700; color: {{ $tenant?->brand_primary ?? '#D32F2F' }}; }
        .brand small { display: block; font-weight: normal; font-size: 10px; color: #666; margin-top: 2px; }
        .meta { text-align: right; font-size: 10px; color: #555; }
        .meta strong { color: #222; }
        h1 { font-size: 15px; margin: 0 0 12px; color: #222; }
        .stats { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .stats td { border: 1px solid #ddd; padding: 8px 10px; width: 25%; }
        .stats .label { font-size: 9px; text-transform: uppercase; color: #777; letter-spacing: 0.4px; }
        .stats .value { font-size: 14px; font-weight: 700; color: #222; margin-top: 2px; }
        .stats .value.accent { color: {{ $tenant?->brand_primary ?? '#D32F2F' }}; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { padding: 6px 6px; text-align: left; border-bottom: 1px solid #ddd; font-size: 9px; }
        table.data th { background: #f5f5f5; color: #555; text-transform: uppercase; letter-spacing: 0.4px; font-size: 8.5px; }
        .amount { text-align: right; font-weight: 600; }
        .method-online { color: #1976d2; }
        .method-offline { color: #444; }
        .status-paid { color: #2e7d32; font-weight: 600; }
        .status-pending { color: #ef6c00; font-weight: 600; }
        .status-failed { color: #c62828; font-weight: 600; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; font-size: 9px; color: #999; }
        .filters { font-size: 10px; color: #555; margin-bottom: 10px; }
        .filters strong { color: #222; }
        .ref { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.5px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">
                {{ $tenant?->name ?? 'Funeral Donations' }}
                <small>{{ $tenant?->tagline ?? 'Donation Report' }}</small>
            </div>
        </div>
        <div class="meta">
            <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}<br>
            <strong>By:</strong> {{ auth()->user()->name }}<br>
            <strong>Records:</strong> {{ number_format($totals['count']) }}
        </div>
    </div>

    <h1>Donation History</h1>

    @if ($filters['status'] || $filters['search'])
        <div class="filters">
            Filters applied:
            @if ($filters['status'])<strong>Status:</strong> {{ ucfirst($filters['status']) }}@endif
            @if ($filters['search'])<strong>Search:</strong> "{{ $filters['search'] }}"@endif
        </div>
    @endif

    <table class="stats">
        <tr>
            <td>
                <div class="label">Total Records</div>
                <div class="value">{{ number_format($totals['count']) }}</div>
            </td>
            <td>
                <div class="label">Paid Amount</div>
                <div class="value accent">GHS {{ number_format($totals['paid_amount'] / 100, 2) }}</div>
            </td>
            <td>
                <div class="label">Pending</div>
                <div class="value">{{ number_format($totals['pending']) }}</div>
            </td>
            <td>
                <div class="label">Failed</div>
                <div class="value">{{ number_format($totals['failed']) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 16%;">Donor</th>
                <th style="width: 10%;">Phone</th>
                <th style="width: 12%;" class="amount">Amount</th>
                <th style="width: 8%;">Method</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 18%;">Reference</th>
                <th style="width: 8%;">SMS</th>
                <th style="width: 8%;">Taken by</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($donations as $d)
                <tr>
                    <td>{{ $d->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ $d->donor_name }}</td>
                    <td>{{ $d->phone }}</td>
                    <td class="amount">{{ $d->currency }} {{ number_format($d->amount / 100, 2) }}</td>
                    <td class="method-{{ $d->payment_method }}">{{ ucfirst($d->payment_method) }}</td>
                    <td class="status-{{ $d->status }}">{{ ucfirst($d->status) }}</td>
                    <td class="ref">{{ $d->paystack_reference ?? '—' }}</td>
                    <td>{{ $d->sms_sent ? 'Sent' : ($d->status === 'paid' ? 'Not sent' : '—') }}</td>
                    <td>{{ $d->user?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $tenant?->name ?? 'Funeral Donations' }} · Confidential · Page <span class="pagenum"></span>
    </div>
</body>
</html>
