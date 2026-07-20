@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">{{ $heading ?? 'Send Bulk SMS' }}</h2>
@if (! empty($lead))
    <p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">{{ $lead }}</p>
@endif

<div class="stats">
    <div class="stat">
        <div class="label">Total SMS Sent</div>
        <div class="value">{{ number_format($campaignTotals['total_sent']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Total Recipients Queued</div>
        <div class="value">{{ number_format($campaignTotals['total_recipients']) }}</div>
    </div>
    <div class="stat">
        <div class="label">Thank-you SMS Sent</div>
        <div class="value">{{ number_format($campaignTotals['thank_you_sent']) }}</div>
    </div>
</div>

@if (session('sms_result'))
    @php $r = session('sms_result'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $r['failed'] === 0 ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 600; margin-bottom: 6px;">
            {{ $r['failed'] === 0 ? 'Campaign queued' : 'Bulk send finished with errors' }}
        </div>
        <div style="color: var(--text-muted); font-size: 14px;">
            Queued: <strong style="color:#66bb6a;">{{ $r['sent'] }}</strong> ·
            Failed: <strong style="color:var(--red);">{{ $r['failed'] }}</strong> ·
            Skipped (duplicates/blank): <strong>{{ $r['skipped'] }}</strong>
        </div>
        @if (! empty($r['campaign_id']))
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 6px; font-family: monospace;">
                Campaign ID: {{ $r['campaign_id'] }}
            </div>
        @endif
        @if (! empty($r['failures']))
            <details style="margin-top: 10px;">
                <summary style="cursor: pointer; color: var(--red); font-size: 13px;">Show failed numbers ({{ count($r['failures']) }})</summary>
                <div style="margin-top: 8px; font-family: monospace; font-size: 12px; color: var(--text-muted); word-break: break-all;">
                    {{ implode(', ', $r['failures']) }}
                </div>
            </details>
        @endif
    </div>
@endif

@if (session('sms_error'))
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid var(--red); color: var(--red);">
        {{ session('sms_error') }}
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.sms.send') }}" id="smsForm"
          data-confirm="This will send the message to the selected recipient scope. Continue?"
          data-confirm-title="Send SMS?"
          data-confirm-icon="warning"
          data-confirm-text="Yes, send"
          data-confirm-danger="1">
        @csrf

        <div class="form-group">
            <div style="font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Recipients</div>
            <div class="scope-options" data-tour="sms-scope">
                <label class="scope-option">
                    <input type="radio" name="scope" value="all" {{ old('scope', 'all') === 'all' ? 'checked' : '' }}>
                    <div>
                        <div class="scope-title">All donors</div>
                        <div class="scope-count">{{ number_format($counts['all']) }} unique numbers</div>
                    </div>
                </label>
                <label class="scope-option">
                    <input type="radio" name="scope" value="paid" {{ old('scope') === 'paid' ? 'checked' : '' }}>
                    <div>
                        <div class="scope-title">Paid only</div>
                        <div class="scope-count">{{ number_format($counts['paid']) }} unique numbers</div>
                    </div>
                </label>
                <label class="scope-option">
                    <input type="radio" name="scope" value="pending" {{ old('scope') === 'pending' ? 'checked' : '' }}>
                    <div>
                        <div class="scope-title">Pending only</div>
                        <div class="scope-count">{{ number_format($counts['pending']) }} unique numbers</div>
                    </div>
                </label>
                <label class="scope-option">
                    <input type="radio" name="scope" value="group" {{ old('scope') === 'group' ? 'checked' : '' }}>
                    <div>
                        <div class="scope-title">Contact group</div>
                        <div class="scope-count">Pick a saved group below</div>
                    </div>
                </label>
                <label class="scope-option">
                    <input type="radio" name="scope" value="custom" {{ old('scope') === 'custom' ? 'checked' : '' }}>
                    <div>
                        <div class="scope-title">Custom numbers</div>
                        <div class="scope-count">Enter numbers below</div>
                    </div>
                </label>
            </div>
            @error('scope')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group" id="groupPickerGroup" style="display: none;">
            <label for="group_id" style="display:block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: var(--text-muted);">Contact group</label>
            <select name="group_id" id="group_id">
                <option value="">— Choose a group —</option>
                @foreach ($groups ?? [] as $g)
                    <option value="{{ $g->id }}" {{ (int) old('group_id') === $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->contacts_count }})</option>
                @endforeach
            </select>
            @if (($groups ?? collect())->isEmpty())
                <div style="font-size: 12px; color: var(--text-dim); margin-top: 6px;">No groups saved. <a href="{{ route('admin.contact-groups.index') }}" style="color: var(--red);">Create one</a> first.</div>
            @endif
        </div>

        <div class="form-group" id="customPhonesGroup" style="display: none;">
            <label for="custom_phones" style="display:block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: var(--text-muted);">Phone numbers</label>
            <textarea name="custom_phones" id="custom_phones" rows="4" placeholder="Separate with commas, spaces, or new lines">{{ old('custom_phones') }}</textarea>
            @error('custom_phones')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="campaign_name" style="display:block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: var(--text-muted);">Campaign name <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
            <input type="text" name="campaign_name" id="campaign_name" maxlength="255" value="{{ old('campaign_name') }}" placeholder="e.g. Funeral update — Saturday">
            @error('campaign_name')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
                <label for="message" style="font-size: 13px; font-weight: 500; color: var(--text-muted);">Message</label>
                <div class="template-picker" data-tour="sms-templates">
                    <span style="font-size: 12px; color: var(--text-dim);">Quick templates:</span>
                    @foreach ($templates as $key => $tpl)
                        <button type="button" class="template-btn" data-template="{{ e($tpl['body']) }}">{{ $tpl['label'] }}</button>
                    @endforeach
                    <button type="button" class="template-btn template-btn-clear" data-template="">Clear</button>
                </div>
            </div>
            <textarea name="message" id="message" rows="5" maxlength="1071" placeholder="Type your message or pick a template above..." required data-tour="sms-message">{{ old('message') }}</textarea>
            <div style="display:flex; justify-content: space-between; font-size: 12px; color: var(--text-dim); margin-top: 6px;">
                <span><span id="msgCount">0</span> / 1071 characters · <span id="segCount">0</span> SMS segment(s)</span>
                <span>Sender: <strong style="color:var(--text-muted);">{{ config('services.sms.sender_id', 'Funeral') }}</strong></span>
            </div>
            @error('message')<div class="error">{{ $message }}</div>@enderror
        </div>

        <input type="hidden" name="kind" value="{{ $kind ?? 'notifications' }}">
        @php
            $sendPerm = match($kind ?? 'notifications') {
                'invitations' => \App\Support\Permissions::SMS_INVITATIONS_SEND,
                'post'        => \App\Support\Permissions::SMS_POST_SEND,
                default       => \App\Support\Permissions::SMS_NOTIFICATIONS_SEND,
            };
        @endphp
        @can($sendPerm)
            <button type="submit" class="btn-primary" style="width: auto; padding: 11px 28px;">Send SMS</button>
        @else
            <div style="padding: 10px 14px; background: rgba(255,179,0,0.1); border: 1px solid rgba(255,179,0,0.35); color: #ffb300; border-radius: 6px; font-size: 13px; display: inline-block;">
                Your role can view this page but not send from here.
            </div>
        @endcan
    </form>
</div>

<h3 style="margin: 24px 0 12px; font-size: 15px; color: var(--text-muted); font-weight: 500;">Recent Campaigns</h3>
<div class="card">
    @if ($campaigns->isEmpty())
        <div class="empty">No campaigns yet.</div>
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
                    <th>Status</th>
                    <th>Sent by</th>
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
                        <td><span class="badge badge-{{ $c->status === 'sent' ? 'paid' : ($c->status === 'failed' ? 'failed' : 'pending') }}">{{ ucfirst($c->status) }}</span></td>
                        <td>{{ $c->user?->name ?? '—' }}</td>
                        <td style="max-width: 320px; color: var(--text-muted); font-size: 13px;">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $c->message }}">{{ $c->message }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<style>
    .scope-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
    .scope-option { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 1px solid var(--border); background: var(--surface-2); border-radius: 8px; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
    .scope-option:hover { border-color: rgba(var(--red-rgb),0.5); }
    .scope-option input[type=radio] { margin-top: 3px; }
    .scope-option:has(input:checked) { border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .scope-title { font-size: 14px; font-weight: 500; color: var(--text); }
    .scope-count { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    textarea { width: 100%; padding: 12px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; }
    textarea::placeholder { color: var(--text-dim); }
    textarea:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
    .template-picker { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .template-btn { background: var(--surface-2); border: 1px solid var(--border); color: var(--text-muted); padding: 4px 10px; border-radius: 999px; font-size: 12px; cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
    .template-btn:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .template-btn-clear { color: var(--text-dim); }
    .template-btn-clear:hover { color: var(--red); border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
</style>

<script>
    (function () {
        const scopeRadios = document.querySelectorAll('input[name=scope]');
        const customGroup = document.getElementById('customPhonesGroup');
        const messageEl = document.getElementById('message');
        const countEl = document.getElementById('msgCount');
        const segEl = document.getElementById('segCount');

        const groupPicker = document.getElementById('groupPickerGroup');
        function toggleCustom() {
            const val = document.querySelector('input[name=scope]:checked')?.value;
            customGroup.style.display = val === 'custom' ? '' : 'none';
            if (groupPicker) groupPicker.style.display = val === 'group' ? '' : 'none';
        }

        function updateCount() {
            const len = messageEl.value.length;
            countEl.textContent = len;
            segEl.textContent = len === 0 ? 0 : Math.ceil(len / 160);
        }

        scopeRadios.forEach(r => r.addEventListener('change', toggleCustom));
        messageEl.addEventListener('input', updateCount);

        document.querySelectorAll('.template-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tpl = btn.dataset.template || '';
                const current = messageEl.value.trim();
                if (current && current !== tpl && !confirm('Replace the current message with this template?')) return;
                messageEl.value = tpl;
                messageEl.focus();
                updateCount();
            });
        });

        toggleCustom();
        updateCount();
    })();
</script>

<script>
    window.__tourKey = 'sms-{{ $kind ?? "notifications" }}-v1';
    window.__tourSteps = [
        {
            target: '[data-tour="sms-scope"]',
            position: 'bottom',
            title: 'Pick your recipients',
            body: 'Send to <strong>all donors</strong>, only those who paid, only pending, or a custom list of phone numbers. Recipient counts are live.',
        },
        {
            target: '[data-tour="sms-templates"]',
            position: 'top',
            title: 'Quick templates',
            body: 'Click any template pill to pre-fill the message box. Templates are grouped by intent — the ones you see here match this page.',
        },
        {
            target: '[data-tour="sms-message"]',
            position: 'top',
            title: 'Your message',
            body: 'Max 1071 characters (7 SMS segments). Placeholders like [NAME], [DATE], [VENUE] are for you to replace before sending. Character and segment counters update live below the box.',
        },
    ];
</script>
@endsection
