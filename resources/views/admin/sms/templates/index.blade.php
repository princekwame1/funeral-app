@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Message Templates</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">
    Customize every SMS your app sends. The <strong>Payment thank-you</strong> template is fired automatically after each paid donation.
</p>

<div class="kind-tabs">
    @foreach ($allKinds as $slug => $label)
        <a href="{{ route('admin.sms-templates.index', ['kind' => $slug]) }}" class="kind-tab {{ $kind === $slug ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

@if (session('super_flash'))
    @php $sf = session('super_flash'); @endphp
    <div class="card" style="margin: 12px 0; border-left: 3px solid {{ $sf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $sf['message'] }}</div>
    </div>
@endif

<div class="tokens-panel">
    <div style="font-weight: 500; font-size: 13px; color: var(--text); margin-bottom: 6px;">Available tokens</div>
    <div class="token-list">
        @foreach ($tokens as $token => $desc)
            <span class="token-pill" title="{{ $desc }}" onclick="navigator.clipboard.writeText('{{ $token }}'); this.classList.add('copied'); setTimeout(()=>this.classList.remove('copied'), 900);">
                <code>{{ $token }}</code>
            </span>
        @endforeach
    </div>
    <div style="font-size: 11px; color: var(--text-dim); margin-top: 6px;">Click any token to copy. Tokens without a live value at send time render as blank.</div>
</div>

<div class="templates-list">
    @forelse ($templates as $t)
        <div class="template-card {{ $t->is_default ? 'is-default' : '' }}">
            <form method="POST" action="{{ route('admin.sms-templates.update', $t) }}">
                @csrf
                <input type="hidden" name="kind" value="{{ $t->kind }}">
                <div class="template-head">
                    <div style="flex: 1;">
                        <div class="form-group" style="margin-bottom: 8px;">
                            <input type="text" name="label" value="{{ $t->label }}" required maxlength="150" class="label-input">
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <div class="slug-group">
                                <span class="slug-prefix">slug</span>
                                <input type="text" name="slug" value="{{ $t->slug }}" pattern="[a-z0-9_-]+" maxlength="60">
                            </div>
                            <label class="default-toggle">
                                <input type="checkbox" name="is_default" value="1" {{ $t->is_default ? 'checked' : '' }}
                                       {{ $t->kind === 'thankyou' ? '' : 'disabled title=only-thankyou' }}>
                                <span>Set as auto-fire default</span>
                            </label>
                            <div class="sort-group">
                                <span style="font-size: 12px; color: var(--text-muted);">Order</span>
                                <input type="number" name="sort_order" value="{{ $t->sort_order }}" min="0" style="width: 70px;">
                            </div>
                        </div>
                    </div>
                    @if ($t->is_default)
                        <span class="badge badge-paid" style="align-self: flex-start;">Auto-fire</span>
                    @endif
                </div>
                <textarea name="body" rows="4" maxlength="1071" required class="body-textarea">{{ $t->body }}</textarea>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; gap: 8px;">
                    <div style="font-size: 11px; color: var(--text-dim);">
                        <span data-count-for="{{ $t->id }}">{{ strlen($t->body) }}</span> / 1071 characters
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="submit" class="btn-primary" style="width: auto; padding: 8px 18px; font-size: 13px;">Save</button>
                        <button type="button" class="btn-verify"
                                onclick="event.preventDefault(); document.getElementById('delete-{{ $t->id }}').submit();"
                                data-swal-confirm="Delete '{{ $t->label }}' template? This cannot be undone."
                                data-confirm-title="Delete template?"
                                data-confirm-icon="error"
                                data-confirm-danger="1"
                                style="color: var(--red);">Delete</button>
                    </div>
                </div>
            </form>
            <form id="delete-{{ $t->id }}" method="POST" action="{{ route('admin.sms-templates.destroy', $t) }}" style="display: none;">@csrf</form>
        </div>
    @empty
        <div class="card empty" style="text-align: center; padding: 40px; color: var(--text-dim);">
            No templates in this category yet. Add one below.
        </div>
    @endforelse
</div>

<div class="card" style="margin-top: 16px;">
    <h3 style="margin: 0 0 12px; font-size: 15px;">+ Add a new {{ strtolower($allKinds[$kind]) }} template</h3>
    <form method="POST" action="{{ route('admin.sms-templates.store') }}">
        @csrf
        <input type="hidden" name="kind" value="{{ $kind }}">
        <div class="grid-2">
            <div class="form-group">
                <label class="field-label">Label</label>
                <input type="text" name="label" required maxlength="150" placeholder="e.g. Weekend reminder">
                @error('label')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label">Slug <span style="color: var(--text-dim); font-weight: 400;">(unique key, lowercase)</span></label>
                <input type="text" name="slug" required maxlength="60" pattern="[a-z0-9_-]+" placeholder="e.g. weekend-reminder">
                @error('slug')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group">
            <label class="field-label">Body</label>
            <textarea name="body" rows="4" maxlength="1071" required placeholder="Type your message. Use tokens above like [DECEASED], [VENUE]…"></textarea>
            @error('body')<div class="error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">Create template</button>
    </form>
</div>

<style>
    .kind-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 20px; flex-wrap: wrap; }
    .kind-tab { padding: 10px 16px; color: var(--text-muted); text-decoration: none; font-size: 14px; border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .kind-tab:hover { color: var(--text); }
    .kind-tab.active { color: var(--text); border-bottom-color: var(--red); font-weight: 500; }

    .tokens-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 14px; margin-bottom: 16px; }
    .token-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .token-pill { background: var(--surface-2); border: 1px solid var(--border); padding: 3px 10px; border-radius: 999px; cursor: pointer; transition: color 0.15s, border-color 0.15s, background 0.15s; }
    .token-pill code { font-size: 11px; color: var(--text-muted); font-family: 'SF Mono', monospace; }
    .token-pill:hover { border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }
    .token-pill:hover code { color: var(--text); }
    .token-pill.copied { border-color: #66bb6a; background: rgba(102, 187, 106, 0.12); }
    .token-pill.copied code { color: #66bb6a; }

    .templates-list { display: flex; flex-direction: column; gap: 12px; }
    .template-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
    .template-card.is-default { border-color: rgba(102, 187, 106, 0.5); box-shadow: 0 0 0 3px rgba(102, 187, 106, 0.08); }
    .template-head { display: flex; gap: 12px; margin-bottom: 10px; align-items: flex-start; }
    .label-input { width: 100%; padding: 8px 12px; background: transparent; border: 1px solid transparent; color: var(--text); font-size: 15px; font-weight: 600; border-radius: 6px; }
    .label-input:hover { border-color: var(--border); }
    .label-input:focus { outline: none; border-color: var(--red); background: var(--surface-2); }
    .slug-group { display: inline-flex; align-items: center; gap: 6px; }
    .slug-prefix { font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; }
    .slug-group input { padding: 5px 10px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; font-size: 12px; font-family: 'SF Mono', monospace; width: 160px; }
    .default-toggle { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-muted); cursor: pointer; }
    .default-toggle input[type=checkbox]:disabled + span { color: var(--text-dim); }
    .sort-group { display: inline-flex; align-items: center; gap: 6px; }
    .sort-group input { padding: 5px 10px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; font-size: 12px; text-align: center; }
    .body-textarea { width: 100%; padding: 12px 14px; background: var(--surface-2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-size: 14px; font-family: inherit; line-height: 1.6; resize: vertical; }
    .body-textarea:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }

    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input[type=text], .card textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    .card input:focus, .card textarea:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; font-family: inherit; }
    .btn-verify:hover { border-color: var(--red); background: rgba(var(--red-rgb), 0.08); color: var(--text); }
</style>

<script>
    document.querySelectorAll('.body-textarea').forEach((ta) => {
        const id = ta.closest('form')?.querySelector('input[name="slug"]')?.value;
        // Live char counter — find the sibling counter by data-count-for id chained to template row
        ta.addEventListener('input', function () {
            const card = ta.closest('.template-card');
            const counter = card?.querySelector('[data-count-for]');
            if (counter) counter.textContent = ta.value.length;
        });
    });
</script>
@endsection
