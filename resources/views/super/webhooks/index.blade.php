@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Webhooks</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">Endpoint URLs to register with your payment and SMS providers, and the raw feed of every event we receive.</p>

<div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <div class="stat">
        <div class="label">Paystack Events</div>
        <div class="value">{{ number_format($counts['paystack']) }}</div>
    </div>
    <div class="stat">
        <div class="label">TextTango Events</div>
        <div class="value">{{ number_format($counts['texttango']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Last 24h</div>
        <div class="value">{{ number_format($counts['last_24h']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Invalid Signature</div>
        <div class="value" style="color: {{ $counts['invalid_sig'] > 0 ? 'var(--red)' : 'var(--text)' }};">{{ number_format($counts['invalid_sig']) }}</div>
    </div>
</div>

<div class="webhook-endpoints">
    <div class="endpoint-card paystack">
        <div class="endpoint-head">
            <div class="endpoint-title">Paystack</div>
            <span class="endpoint-status ok">Ready</span>
        </div>
        <div class="endpoint-url">
            <code>{{ $urls['paystack'] }}</code>
            <button type="button" class="copy-btn" data-copy="{{ $urls['paystack'] }}">Copy</button>
        </div>
        <div class="endpoint-steps">
            <strong>How to register:</strong>
            <ol>
                <li>Log in at <a href="https://dashboard.paystack.com" target="_blank" rel="noopener">dashboard.paystack.com</a>.</li>
                <li>Go to <em>Settings → API Keys &amp; Webhooks → Webhook URL</em>.</li>
                <li>Paste the URL above into <em>Live URL</em> (or <em>Test URL</em> for sandbox).</li>
                <li>Save. Paystack sends a test event; check the log below to confirm arrival.</li>
            </ol>
            <div class="endpoint-note">
                <strong>Signature:</strong> HMAC-SHA512 over the raw body using your <code>PAYSTACK_SECRET_KEY</code>. Verified automatically on each request.
                <br><strong>Events handled:</strong> <code>charge.success</code>, <code>charge.failed</code>, <code>charge.abandoned</code>, <code>charge.dispute.create</code>, <code>refund.processed</code>.
            </div>
        </div>
    </div>

    <div class="endpoint-card texttango">
        <div class="endpoint-head">
            <div class="endpoint-title">TextTango</div>
            <span class="endpoint-status ok">Ready</span>
        </div>
        <div class="endpoint-url">
            <code>{{ $urls['texttango'] }}</code>
            <button type="button" class="copy-btn" data-copy="{{ $urls['texttango'] }}">Copy</button>
        </div>
        <div class="endpoint-steps">
            <strong>How to register:</strong>
            <ol>
                <li>Log in at <a href="https://app.texttango.com" target="_blank" rel="noopener">app.texttango.com</a>.</li>
                <li>Go to <em>Profile → API Tokens → Webhook URL</em>.</li>
                <li>Paste the URL above.</li>
                <li>Save. Delivery events will land in the log within seconds of each SMS.</li>
            </ol>
            <div class="endpoint-note">
                <strong>Signature:</strong> HMAC-SHA256 over the raw body using your <code>SMS_API_KEY</code>. Verified automatically.
                <br><strong>Events handled:</strong> <code>message.delivered</code>, <code>message.sent</code>, <code>message.failed</code>, <code>message.undelivered</code>, <code>campaign.completed</code>.
            </div>
        </div>
    </div>
</div>

<div class="local-dev-note">
    <strong>Local dev tip:</strong>
    Providers can't reach <code>127.0.0.1</code>. Tunnel your dev server with
    <code>ngrok http 8000</code> (or <code>cloudflared tunnel --url http://localhost:8000</code>),
    then paste the public URL from the tunnel into the provider's webhook field. Signatures still verify normally.
</div>

<div style="display: flex; justify-content: space-between; align-items: baseline; margin: 24px 0 12px;">
    <h3 style="margin: 0; font-size: 15px; color: var(--text-muted); font-weight: 500;">Recent events</h3>
    <div class="filter-pills">
        <a href="{{ route('super.webhooks.index') }}" class="filter-pill {{ ! $provider ? 'active' : '' }}">All</a>
        <a href="{{ route('super.webhooks.index', ['provider' => 'paystack']) }}" class="filter-pill {{ $provider === 'paystack' ? 'active' : '' }}">Paystack</a>
        <a href="{{ route('super.webhooks.index', ['provider' => 'texttango']) }}" class="filter-pill {{ $provider === 'texttango' ? 'active' : '' }}">TextTango</a>
    </div>
</div>

<div class="card">
    @if ($events->isEmpty())
        <div class="empty" style="text-align: center; padding: 40px; color: var(--text-dim);">
            No events received yet. Once a provider fires a webhook to this app, it lands here.
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Received</th>
                    <th>Provider</th>
                    <th>Event</th>
                    <th>Reference</th>
                    <th>Sig</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $e)
                    <tr>
                        <td>{{ $e->received_at->format('d M Y H:i:s') }}</td>
                        <td><span class="badge badge-method-{{ $e->provider === 'paystack' ? 'online' : 'offline' }}">{{ ucfirst($e->provider) }}</span></td>
                        <td style="font-family: monospace; font-size: 12px;">{{ $e->event ?? '—' }}</td>
                        <td style="font-family: monospace; font-size: 12px; color: var(--text-dim);">{{ $e->reference ?? '—' }}</td>
                        <td>
                            @if ($e->signature_ok)
                                <span class="badge badge-paid" title="Signature verified">✓</span>
                            @else
                                <span class="badge badge-failed" title="Signature invalid">✗</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $e->response_status < 300 ? 'badge-paid' : ($e->response_status < 400 ? 'badge-pending' : 'badge-failed') }}">{{ $e->response_status }}</span>
                        </td>
                        <td>
                            <button type="button" class="btn-verify" onclick="var el=document.getElementById('event-{{ $e->id }}'); el.style.display = el.style.display === 'table-row' ? 'none' : 'table-row';">Payload</button>
                        </td>
                    </tr>
                    <tr id="event-{{ $e->id }}" style="display: none;">
                        <td colspan="7" style="background: var(--surface-2); padding: 14px;">
                            @if ($e->error)
                                <div style="color: var(--red); font-size: 12px; margin-bottom: 8px;"><strong>Error:</strong> {{ $e->error }}</div>
                            @endif
                            <pre style="margin: 0; padding: 12px; background: rgba(0,0,0,0.35); border: 1px solid var(--border); border-radius: 6px; font-size: 11px; color: var(--text-muted); overflow: auto; max-height: 320px; white-space: pre-wrap; word-break: break-all;">{{ json_encode($e->decodedPayload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $events->links() }}</div>
    @endif
</div>

<style>
    .webhook-endpoints { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .endpoint-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; border-top: 3px solid var(--red); }
    .endpoint-card.paystack { border-top-color: #64b5f6; }
    .endpoint-card.texttango { border-top-color: #66bb6a; }
    .endpoint-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .endpoint-title { font-size: 18px; font-weight: 700; color: var(--text); }
    .endpoint-status.ok { background: rgba(102,187,106,0.15); color: #66bb6a; border: 1px solid rgba(102,187,106,0.4); padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
    .endpoint-url { display: flex; gap: 6px; align-items: stretch; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 4px; margin-bottom: 14px; }
    .endpoint-url code { flex: 1; padding: 8px 12px; font-family: 'SF Mono', monospace; font-size: 12px; color: var(--text); overflow: auto; white-space: nowrap; }
    .copy-btn { background: var(--red); color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
    .copy-btn:hover { background: var(--dark-red); }
    .copy-btn.copied { background: #66bb6a; }
    .endpoint-steps { font-size: 13px; color: var(--text-muted); line-height: 1.55; }
    .endpoint-steps strong { color: var(--text); }
    .endpoint-steps ol { margin: 6px 0 12px 20px; padding: 0; }
    .endpoint-steps ol li { margin-bottom: 4px; }
    .endpoint-steps a { color: var(--red); }
    .endpoint-steps code { background: var(--surface-2); border: 1px solid var(--border); padding: 1px 6px; border-radius: 4px; font-size: 11px; }
    .endpoint-note { font-size: 12px; color: var(--text-dim); padding: 10px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 6px; margin-top: 8px; line-height: 1.6; }

    .local-dev-note { padding: 12px 16px; background: rgba(255,179,0,0.08); border: 1px solid rgba(255,179,0,0.35); border-radius: 8px; color: var(--text-muted); font-size: 13px; line-height: 1.6; margin-bottom: 20px; }
    .local-dev-note strong { color: #ffb300; }
    .local-dev-note code { background: var(--surface-2); border: 1px solid var(--border); padding: 1px 6px; border-radius: 4px; font-size: 12px; color: var(--text); }

    .filter-pills { display: flex; gap: 6px; }
    .filter-pill { padding: 4px 12px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text-muted); border-radius: 999px; font-size: 12px; text-decoration: none; }
    .filter-pill.active { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.15); }
    .filter-pill:hover { color: var(--text); border-color: var(--red); }

    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 4px 12px; border-radius: 999px; font-size: 11px; cursor: pointer; font-family: inherit; }
    .btn-verify:hover { color: var(--text); border-color: var(--red); }
</style>

<script>
    document.querySelectorAll('.copy-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(btn.dataset.copy);
                var original = btn.textContent;
                btn.textContent = 'Copied';
                btn.classList.add('copied');
                setTimeout(function () { btn.textContent = original; btn.classList.remove('copied'); }, 1500);
            } catch (e) {
                if (window.Swal) Swal.toast({ type: 'error', title: 'Copy failed', body: 'Copy the URL manually.' });
            }
        });
    });
</script>
@endsection
