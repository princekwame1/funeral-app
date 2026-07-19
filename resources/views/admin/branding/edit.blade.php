@extends('layouts.app')

@section('content')
<div class="branding-page">
    <div class="branding-header">
        <div>
            <h2 style="margin: 0 0 4px;">Branding</h2>
            <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Make the app look like it belongs to <strong style="color: var(--text);">{{ $tenant->name }}</strong>. Changes save to this tenant only.</p>
        </div>
    </div>

    @if (session('branding_flash'))
        @php $bf = session('branding_flash'); @endphp
        <div class="flash {{ $bf['ok'] ? 'flash-success' : 'flash-error' }}">
            <span class="flash-dot"></span>{{ $bf['message'] }}
        </div>
    @endif

    <form method="POST" action="{{ route('super.branding.update') }}" enctype="multipart/form-data" id="brandingForm">
        @csrf

        <div class="branding-layout">
            <div class="branding-main">
                {{-- Identity card --}}
                <section class="section-card" data-tour="brand-identity">
                    <div class="section-head">
                        <div class="section-title">Identity</div>
                        <div class="section-hint">The name and tagline that appear across the app.</div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="field-label" for="name">Tenant name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required maxlength="150" data-preview="name">
                            @error('name')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="field-label" for="tagline">Tagline</label>
                            <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $tenant->tagline) }}" maxlength="200" placeholder="e.g. In loving memory of…" data-preview="tagline">
                            @error('tagline')<div class="error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                {{-- Colors card --}}
                <section class="section-card" data-tour="brand-colors">
                    <div class="section-head">
                        <div class="section-title">Colors</div>
                        <div class="section-hint">Applied to buttons, badges, focus rings and the sidebar accent.</div>
                    </div>
                    <div class="grid-2">
                        <div class="color-picker">
                            <label class="field-label" for="brand_primary">Primary</label>
                            <div class="color-row">
                                <input type="color" name="brand_primary" id="brand_primary" value="{{ old('brand_primary', $tenant->brand_primary ?? '#D32F2F') }}" data-preview="primary">
                                <span class="color-hex" data-hex-for="brand_primary">{{ old('brand_primary', $tenant->brand_primary ?? '#D32F2F') }}</span>
                            </div>
                            @error('brand_primary')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="color-picker">
                            <label class="field-label" for="brand_accent">Accent</label>
                            <div class="color-row">
                                <input type="color" name="brand_accent" id="brand_accent" value="{{ old('brand_accent', $tenant->brand_accent ?? '#9A0007') }}" data-preview="accent">
                                <span class="color-hex" data-hex-for="brand_accent">{{ old('brand_accent', $tenant->brand_accent ?? '#9A0007') }}</span>
                            </div>
                            @error('brand_accent')<div class="error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                {{-- Images card --}}
                <section class="section-card" data-tour="brand-images">
                    <div class="section-head">
                        <div class="section-title">Images</div>
                        <div class="section-hint">Drag & drop a file or paste a URL. Uploaded files replace the current image immediately.</div>
                    </div>

                    <div class="image-grid">
                    @foreach ([
                        ['key' => 'logo', 'column' => 'logo_url', 'label' => 'Logo', 'hint' => 'Sidebar icon. Square.', 'max' => '2MB', 'ratio' => '1/1'],
                        ['key' => 'splash', 'column' => 'splash_image_url', 'label' => 'Splash / hero', 'hint' => 'Dashboard banner.', 'max' => '5MB', 'ratio' => '16/9'],
                        ['key' => 'background', 'column' => 'background_image_url', 'label' => 'Background', 'hint' => 'Behind login screen.', 'max' => '5MB', 'ratio' => '16/9'],
                        ['key' => 'favicon', 'column' => 'favicon_url', 'label' => 'Favicon', 'hint' => 'Tab icon 32×32+.', 'max' => '512KB', 'ratio' => '1/1'],
                    ] as $img)
                        <div class="image-block" data-image="{{ $img['key'] }}">
                            <div class="image-info">
                                <div class="image-label">{{ $img['label'] }}</div>
                                <div class="image-sub">{{ $img['hint'] }} · max {{ $img['max'] }}</div>
                            </div>

                            <div class="image-drop" style="aspect-ratio: {{ $img['ratio'] }};">
                                <img class="image-drop-img {{ $tenant->{$img['column']} ? '' : 'is-empty' }}" src="{{ $tenant->{$img['column']} ?: '' }}" alt="">
                                <div class="image-drop-empty {{ $tenant->{$img['column']} ? 'is-hidden' : '' }}">
                                    <div class="image-drop-icon">+</div>
                                    <div class="image-drop-text">Drop or click</div>
                                </div>
                                <input type="file" name="{{ $img['key'] }}_file" accept="image/*" class="image-drop-input" data-preview-file="{{ $img['key'] }}">
                            </div>

                            <input type="url" name="{{ $img['column'] }}" value="{{ old($img['column'], $tenant->{$img['column']}) }}" placeholder="…or paste URL" maxlength="500" class="image-url-input" data-preview-url="{{ $img['key'] }}">

                            <label class="image-clear {{ $tenant->{$img['column']} ? '' : 'is-hidden' }}">
                                <input type="checkbox" name="clear_{{ $img['key'] }}" value="1"> Remove
                            </label>

                            @error($img['column'])<div class="error">{{ $message }}</div>@enderror
                            @error($img['key'] . '_file')<div class="error">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    </div>
                </section>

                {{-- Contact & SMS card --}}
                <section class="section-card">
                    <div class="section-head">
                        <div class="section-title">Contact & SMS</div>
                        <div class="section-hint">Optional contact details. SMS Sender ID must be pre-approved on TextTango.</div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label class="field-label" for="contact_email">Contact email</label>
                            <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $tenant->contact_email) }}" maxlength="150" placeholder="family@example.com">
                        </div>
                        <div class="form-group">
                            <label class="field-label" for="contact_phone">Contact phone</label>
                            <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $tenant->contact_phone) }}" maxlength="30" placeholder="+233 …">
                        </div>
                        <div class="form-group">
                            <label class="field-label" for="sms_sender_id">SMS Sender ID</label>
                            <input type="text" name="sms_sender_id" id="sms_sender_id" value="{{ old('sms_sender_id', $tenant->sms_sender_id) }}" maxlength="20" placeholder="e.g. Essence">
                        </div>
                    </div>
                </section>

                <div class="save-bar">
                    <button type="submit" class="btn-primary" style="width:auto; padding: 11px 28px;">Save branding</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn-outline">Cancel</a>
                </div>
            </div>

            {{-- Live preview column --}}
            <aside class="branding-preview" data-tour="brand-preview">
                <div class="preview-sticky">
                    <div class="preview-label">LIVE PREVIEW</div>

                    <div class="preview-mini" id="previewMini">
                        <div class="preview-mini-sidebar">
                            <div class="preview-mini-brand">
                                <div class="preview-mini-logo" id="previewLogo">
                                    @if ($tenant->logo_url)<img src="{{ $tenant->logo_url }}" alt="">@endif
                                </div>
                                <div class="preview-mini-title">
                                    <div class="preview-mini-name" id="previewName">{{ $tenant->name }}</div>
                                    <div class="preview-mini-role">Admin</div>
                                </div>
                            </div>
                            <div class="preview-mini-nav">
                                <div class="preview-mini-item preview-mini-active" id="previewActiveItem">Dashboard</div>
                                <div class="preview-mini-item">Donations</div>
                                <div class="preview-mini-item">SMS</div>
                            </div>
                        </div>
                        <div class="preview-mini-content">
                            <div class="preview-mini-hero" id="previewHero">
                                @if ($tenant->splash_image_url)<img src="{{ $tenant->splash_image_url }}" alt="">@endif
                                <div class="preview-mini-hero-fade"></div>
                                <div class="preview-mini-hero-text">
                                    <div class="preview-mini-heading">Welcome</div>
                                    <div class="preview-mini-tagline" id="previewTagline">{{ $tenant->tagline ?? 'Manage donations & SMS' }}</div>
                                </div>
                            </div>
                            <div class="preview-mini-btns">
                                <div class="preview-mini-btn-primary" id="previewBtnPrimary">Take donation</div>
                                <div class="preview-mini-btn-outline">Send SMS</div>
                            </div>
                        </div>
                    </div>

                    <div class="preview-swatches">
                        <div class="preview-swatch">
                            <span class="preview-swatch-dot" id="previewPrimaryDot"></span>
                            <div>
                                <div class="preview-swatch-label">Primary</div>
                                <div class="preview-swatch-val" id="previewPrimaryHex">{{ $tenant->brand_primary }}</div>
                            </div>
                        </div>
                        <div class="preview-swatch">
                            <span class="preview-swatch-dot" id="previewAccentDot"></span>
                            <div>
                                <div class="preview-swatch-label">Accent</div>
                                <div class="preview-swatch-val" id="previewAccentHex">{{ $tenant->brand_accent }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="preview-note">Changes here are just visual — hit Save to apply them across the app.</div>
                </div>
            </aside>
        </div>
    </form>
</div>

<style>
    .branding-page { width: 100%; }
    .branding-header { margin-bottom: 20px; }
    .flash { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; border: 1px solid var(--border); background: var(--surface); }
    .flash-success { border-left: 3px solid #66bb6a; }
    .flash-error { border-left: 3px solid var(--red); }
    .flash-dot { width: 8px; height: 8px; border-radius: 50%; }
    .flash-success .flash-dot { background: #66bb6a; }
    .flash-error .flash-dot { background: var(--red); }

    .branding-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 20px; align-items: flex-start; }
    @media (max-width: 1200px) { .branding-layout { grid-template-columns: 1fr; } }
    .branding-main { display: flex; flex-direction: column; gap: 16px; }

    .section-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
    .section-head { margin-bottom: 14px; }
    .section-title { font-size: 15px; font-weight: 600; color: var(--text); }
    .section-hint { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
    .field-label { display: block; font-size: 12px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.4px; }
    .form-group input[type=text], .form-group input[type=email], .form-group input[type=url], .image-url-input { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 8px; font-size: 14px; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; }
    .form-group input:focus, .image-url-input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }

    .color-picker { display: flex; flex-direction: column; }
    .color-row { display: flex; align-items: center; gap: 12px; padding: 8px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; }
    .color-row input[type=color] { width: 44px; height: 44px; padding: 0; border: none; background: transparent; cursor: pointer; }
    .color-hex { font-family: 'SF Mono', 'Monaco', 'Consolas', monospace; font-size: 13px; color: var(--text); text-transform: uppercase; }

    .image-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    @media (max-width: 1400px) { .image-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .image-grid { grid-template-columns: 1fr; } }
    .image-block { display: flex; flex-direction: column; gap: 8px; padding: 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; }
    .image-info { }
    .image-label { font-size: 13px; font-weight: 500; color: var(--text); }
    .image-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .image-drop { position: relative; background: rgba(0,0,0,0.35); border: 1px dashed var(--border); border-radius: 8px; overflow: hidden; cursor: pointer; transition: border-color 0.15s, background 0.15s; width: 100%; }
    .image-drop:hover { border-color: var(--red); background: rgba(var(--red-rgb),0.06); }
    .image-drop.is-dragover { border-color: var(--red); background: rgba(var(--red-rgb),0.12); }
    .image-drop-img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .image-drop-img.is-empty { display: none; }
    .image-drop-empty { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: var(--text-dim); pointer-events: none; }
    .image-drop-empty.is-hidden { display: none; }
    .image-drop-icon { font-size: 28px; font-weight: 300; color: var(--text-muted); line-height: 1; }
    .image-drop-text { font-size: 11px; color: var(--text-muted); }
    .image-drop-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .image-url-input { font-size: 12px !important; padding: 8px 10px !important; width: 100%; }
    .image-clear { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-dim); cursor: pointer; }
    .image-clear.is-hidden { display: none; }
    .image-clear input[type=checkbox] { accent-color: var(--red); }

    .save-bar { display: flex; gap: 10px; align-items: center; padding-top: 4px; }
    .btn-outline { padding: 11px 20px; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; }
    .btn-outline:hover { color: var(--text); border-color: var(--red); }

    /* --- Live preview --- */
    .branding-preview { position: relative; }
    .preview-sticky { position: sticky; top: 76px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
    .preview-label { font-size: 10px; letter-spacing: 1px; color: var(--text-dim); margin-bottom: 10px; }
    .preview-mini { background: var(--black); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; display: grid; grid-template-columns: 90px 1fr; height: 200px; }
    .preview-mini-sidebar { background: var(--surface); border-right: 1px solid var(--border); padding: 8px 6px; display: flex; flex-direction: column; gap: 6px; }
    .preview-mini-brand { display: flex; align-items: center; gap: 6px; padding: 4px; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
    .preview-mini-logo { width: 20px; height: 20px; border-radius: 4px; background: var(--red); overflow: hidden; flex-shrink: 0; }
    .preview-mini-logo img { width: 100%; height: 100%; object-fit: cover; }
    .preview-mini-title { min-width: 0; }
    .preview-mini-name { font-size: 9px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .preview-mini-role { font-size: 7px; color: var(--text-dim); }
    .preview-mini-nav { display: flex; flex-direction: column; gap: 3px; margin-top: 4px; }
    .preview-mini-item { font-size: 9px; color: var(--text-muted); padding: 3px 6px; border-radius: 3px; }
    .preview-mini-active { background: rgba(var(--red-rgb),0.15); color: var(--text); border: 1px solid rgba(var(--red-rgb),0.4); }
    .preview-mini-content { padding: 8px; display: flex; flex-direction: column; gap: 6px; }
    .preview-mini-hero { position: relative; flex: 1; border-radius: 6px; overflow: hidden; background: var(--surface-2); }
    .preview-mini-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .preview-mini-hero-fade { position: absolute; inset: 0; background: linear-gradient(to right, rgba(0,0,0,0.85), rgba(0,0,0,0.2)); }
    .preview-mini-hero-text { position: absolute; inset: 0; padding: 8px; display: flex; flex-direction: column; justify-content: flex-end; color: var(--text); }
    .preview-mini-heading { font-size: 10px; font-weight: 600; }
    .preview-mini-tagline { font-size: 8px; color: rgba(255,255,255,0.7); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .preview-mini-btns { display: flex; gap: 4px; }
    .preview-mini-btn-primary, .preview-mini-btn-outline { font-size: 8px; padding: 4px 8px; border-radius: 4px; }
    .preview-mini-btn-primary { background: var(--red); color: #fff; }
    .preview-mini-btn-outline { border: 1px solid var(--border); color: var(--text-muted); }

    .preview-swatches { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; }
    .preview-swatch { display: flex; align-items: center; gap: 10px; padding: 8px 10px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; }
    .preview-swatch-dot { width: 24px; height: 24px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); }
    .preview-swatch-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.4px; }
    .preview-swatch-val { font-size: 12px; color: var(--text); font-family: 'SF Mono', 'Monaco', monospace; }

    .preview-note { margin-top: 12px; font-size: 11px; color: var(--text-dim); line-height: 1.5; }
</style>

<script>
    (function () {
        function $ (sel, root) { return (root || document).querySelector(sel); }
        function $$ (sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

        // --- Live preview bindings ---
        var previewName = $('#previewName');
        var previewTagline = $('#previewTagline');
        var previewLogo = $('#previewLogo');
        var previewHero = $('#previewHero');
        var previewActiveItem = $('#previewActiveItem');
        var previewBtnPrimary = $('#previewBtnPrimary');
        var previewPrimaryDot = $('#previewPrimaryDot');
        var previewAccentDot = $('#previewAccentDot');
        var previewPrimaryHex = $('#previewPrimaryHex');
        var previewAccentHex = $('#previewAccentHex');

        var nameInput = $('#name');
        nameInput.addEventListener('input', function () {
            previewName.textContent = nameInput.value || 'Tenant';
        });

        var taglineInput = $('#tagline');
        taglineInput.addEventListener('input', function () {
            previewTagline.textContent = taglineInput.value || 'Manage donations & SMS';
        });

        function applyColor(input, dot, hex) {
            function refresh() {
                dot.style.background = input.value;
                hex.textContent = input.value.toUpperCase();
                if (input.dataset.preview === 'primary') {
                    previewActiveItem.style.background = input.value + '26';
                    previewActiveItem.style.borderColor = input.value + '66';
                    previewBtnPrimary.style.background = input.value;
                    previewLogo.style.background = input.value;
                }
            }
            input.addEventListener('input', refresh);
            refresh();
        }
        applyColor($('#brand_primary'), previewPrimaryDot, previewPrimaryHex);
        applyColor($('#brand_accent'), previewAccentDot, previewAccentHex);

        // Sync color hex labels next to picker
        $$('.color-hex').forEach(function (span) {
            var id = span.dataset.hexFor;
            var input = document.getElementById(id);
            if (!input) return;
            input.addEventListener('input', function () { span.textContent = input.value.toUpperCase(); });
        });

        // --- Image drop zones ---
        $$('.image-block').forEach(function (block) {
            var key = block.dataset.image;
            var drop = block.querySelector('.image-drop');
            var input = block.querySelector('.image-drop-input');
            var urlInput = block.querySelector('.image-url-input');
            var imgEl = block.querySelector('.image-drop-img');
            var emptyEl = block.querySelector('.image-drop-empty');
            var clearEl = block.querySelector('.image-clear');
            var clearCheckbox = clearEl?.querySelector('input[type=checkbox]');

            function showImage(src) {
                imgEl.src = src;
                imgEl.classList.remove('is-empty');
                emptyEl.classList.add('is-hidden');
                if (clearEl) clearEl.classList.remove('is-hidden');
                if (clearCheckbox) clearCheckbox.checked = false;

                // Update the sidebar preview for logo/splash
                if (key === 'logo') {
                    previewLogo.innerHTML = '<img src="' + src + '" alt="">';
                }
                if (key === 'splash') {
                    var existing = previewHero.querySelector('img');
                    if (existing) existing.src = src;
                    else previewHero.insertAdjacentHTML('afterbegin', '<img src="' + src + '" alt="">');
                }
            }

            function clearImage() {
                imgEl.src = '';
                imgEl.classList.add('is-empty');
                emptyEl.classList.remove('is-hidden');
                if (clearEl) clearEl.classList.add('is-hidden');
                if (key === 'logo') previewLogo.innerHTML = '';
                if (key === 'splash') {
                    var existing = previewHero.querySelector('img');
                    if (existing) existing.remove();
                }
            }

            input.addEventListener('change', function () {
                if (!input.files || !input.files[0]) return;
                var reader = new FileReader();
                reader.onload = function (e) { showImage(e.target.result); };
                reader.readAsDataURL(input.files[0]);
            });

            urlInput.addEventListener('input', function () {
                if (urlInput.value.trim()) showImage(urlInput.value.trim());
                else if (!input.files || !input.files[0]) clearImage();
            });

            if (clearCheckbox) {
                clearCheckbox.addEventListener('change', function () {
                    if (clearCheckbox.checked) clearImage();
                });
            }

            // Drag & drop
            ['dragenter', 'dragover'].forEach(function (evt) {
                drop.addEventListener(evt, function (e) { e.preventDefault(); drop.classList.add('is-dragover'); });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                drop.addEventListener(evt, function (e) { e.preventDefault(); drop.classList.remove('is-dragover'); });
            });
            drop.addEventListener('drop', function (e) {
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });

        // --- Pre-flight guard against PHP post_max_size (default 8MB) ---
        var form = document.getElementById('brandingForm');
        if (form) {
            // Per-slot server-side maxima, in bytes
            var perSlotMax = { logo: 2 * 1024 * 1024, splash: 5 * 1024 * 1024, background: 5 * 1024 * 1024, favicon: 512 * 1024 };
            // Total post budget — a bit under 8 MB to leave room for CSRF/form fields
            var TOTAL_BUDGET_BYTES = 7 * 1024 * 1024;

            function humanSize(n) {
                if (n >= 1048576) return (n / 1048576).toFixed(1) + ' MB';
                if (n >= 1024) return (n / 1024).toFixed(1) + ' KB';
                return n + ' B';
            }

            form.addEventListener('submit', function (e) {
                var oversize = [];
                var total = 0;
                Object.keys(perSlotMax).forEach(function (key) {
                    var el = form.querySelector('input[name="' + key + '_file"]');
                    if (!el || !el.files || !el.files[0]) return;
                    var f = el.files[0];
                    total += f.size;
                    if (f.size > perSlotMax[key]) oversize.push({ label: key, size: f.size, limit: perSlotMax[key] });
                });
                if (oversize.length === 0 && total <= TOTAL_BUDGET_BYTES) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                var lines = oversize.map(function (o) { return '<li><strong>' + o.label + '</strong>: ' + humanSize(o.size) + ' (max ' + humanSize(o.limit) + ')</li>'; });
                var body = '';
                if (oversize.length) body += 'Some files exceed their individual size limit:<ul style="text-align:left; margin:8px 0 4px 20px; padding:0;">' + lines.join('') + '</ul>';
                if (total > TOTAL_BUDGET_BYTES) body += '<div style="margin-top:8px;">Combined upload size is <strong>' + humanSize(total) + '</strong>, which is above the ' + humanSize(TOTAL_BUDGET_BYTES) + ' server upload budget. Try smaller images or upload one slot at a time.</div>';
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Images too large', body: body, html: true, confirmText: 'Got it', showCancel: false });
                } else {
                    alert('Images too large — please shrink and try again.');
                }
            }, true);
        }
    })();
</script>

<script>
    window.__tourKey = 'branding-v1';
    window.__tourSteps = [
        {
            target: '[data-tour="brand-identity"]',
            position: 'bottom',
            title: 'Name & tagline',
            body: 'The name shows in the sidebar, browser tab and every PDF export. The tagline appears on the login screen and dashboard hero.',
        },
        {
            target: '[data-tour="brand-colors"]',
            position: 'bottom',
            title: 'Brand colors',
            body: '<strong>Primary</strong> drives buttons, active nav, and status accents. <strong>Accent</strong> is used for hover states and the darker shade of the primary. Watch the preview on the right update instantly.',
        },
        {
            target: '[data-tour="brand-images"]',
            position: 'top',
            title: 'Four image slots',
            body: 'Drop a file OR paste a URL — <strong>Logo</strong> (sidebar), <strong>Splash</strong> (dashboard hero), <strong>Background</strong> (login page), <strong>Favicon</strong> (browser tab). Uploaded files auto-preview.',
        },
        {
            target: '[data-tour="brand-preview"]',
            position: 'left',
            title: 'Live preview',
            body: 'This mini-app renders exactly how your tenant will look. Everything you change on the left animates here in real time — no need to save first.',
        },
    ];
</script>
@endsection
