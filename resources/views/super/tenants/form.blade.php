@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">{{ $tenant->exists ? 'Edit tenant' : 'Add tenant' }}</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">Every tenant is an isolated funeral/family. Users, donations and SMS are scoped to their tenant.</p>

<div class="card">
    <form method="POST" action="{{ $tenant->exists ? route('super.tenants.update', $tenant) : route('super.tenants.store') }}">
        @csrf

        <div class="grid-2">
            <div class="form-group">
                <label class="field-label" for="name">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required maxlength="150">
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="slug">Slug <span style="color: var(--text-dim); font-weight: 400;">(optional — auto-generated)</span></label>
                <input type="text" name="slug" id="slug" value="{{ old('slug', $tenant->slug) }}" maxlength="80" placeholder="auto-generated from name">
                @error('slug')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-group">
            <label class="field-label" for="tagline">Tagline</label>
            <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $tenant->tagline) }}" maxlength="200" placeholder="e.g. In loving memory of...">
            @error('tagline')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="field-label" for="contact_email">Contact email</label>
                <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $tenant->contact_email) }}" maxlength="150">
                @error('contact_email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="contact_phone">Contact phone</label>
                <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $tenant->contact_phone) }}" maxlength="30">
                @error('contact_phone')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <h3 class="section-heading">Branding</h3>
        <div class="grid-3">
            <div class="form-group">
                <label class="field-label" for="brand_primary">Primary color</label>
                <input type="color" name="brand_primary" id="brand_primary" value="{{ old('brand_primary', $tenant->brand_primary ?? '#D32F2F') }}" style="height: 44px; padding: 4px;">
                @error('brand_primary')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="brand_accent">Accent color</label>
                <input type="color" name="brand_accent" id="brand_accent" value="{{ old('brand_accent', $tenant->brand_accent ?? '#9A0007') }}" style="height: 44px; padding: 4px;">
                @error('brand_accent')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="grid-2" style="margin-top: 10px;">
            <div class="form-group">
                <label class="field-label" for="logo_url">Logo URL</label>
                <input type="url" name="logo_url" id="logo_url" value="{{ old('logo_url', $tenant->logo_url) }}" maxlength="500" placeholder="https://…/logo.png">
                @error('logo_url')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="splash_image_url">Splash / hero image URL</label>
                <input type="url" name="splash_image_url" id="splash_image_url" value="{{ old('splash_image_url', $tenant->splash_image_url) }}" maxlength="500" placeholder="https://…/hero.jpg">
                @error('splash_image_url')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="background_image_url">Background image URL</label>
                <input type="url" name="background_image_url" id="background_image_url" value="{{ old('background_image_url', $tenant->background_image_url) }}" maxlength="500" placeholder="https://…/bg.jpg">
                @error('background_image_url')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="favicon_url">Favicon URL</label>
                <input type="url" name="favicon_url" id="favicon_url" value="{{ old('favicon_url', $tenant->favicon_url) }}" maxlength="500" placeholder="https://…/favicon.png">
                @error('favicon_url')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="grid-2" style="display:none;"></div>

        <h3 class="section-heading">Integrations</h3>
        <div class="grid-2">
            <div class="form-group">
                <label class="field-label" for="sms_sender_id">SMS Sender ID</label>
                <input type="text" name="sms_sender_id" id="sms_sender_id" value="{{ old('sms_sender_id', $tenant->sms_sender_id) }}" maxlength="20" placeholder="e.g. Essence">
                @error('sms_sender_id')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="paystack_public">Paystack public key</label>
                <input type="text" name="paystack_public" id="paystack_public" value="{{ old('paystack_public', $tenant->paystack_public) }}" placeholder="pk_test_…" maxlength="200">
                @error('paystack_public')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group">
            <label class="field-label" for="paystack_secret">Paystack secret key</label>
            <input type="password" name="paystack_secret" id="paystack_secret" value="{{ old('paystack_secret', $tenant->paystack_secret) }}" placeholder="sk_test_…" maxlength="200" autocomplete="new-password">
            @error('paystack_secret')<div class="error">{{ $message }}</div>@enderror
        </div>

        <h3 class="section-heading">Plan & lifecycle</h3>
        <div class="grid-3">
            <div class="form-group">
                <label class="field-label" for="plan">Plan</label>
                <select name="plan" id="plan">
                    @foreach (\App\Support\Plans::all() as $slug => $def)
                        <option value="{{ $slug }}" {{ old('plan', $tenant->plan ?? 'free') === $slug ? 'selected' : '' }}>
                            {{ $def['name'] }} — {{ $def['sms_monthly'] ? number_format($def['sms_monthly']) . ' SMS/mo' : 'Unlimited SMS' }}, {{ $def['donations_total'] ? number_format($def['donations_total']) . ' donations' : 'Unlimited donations' }}
                        </option>
                    @endforeach
                </select>
                @error('plan')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="sms_limit_monthly">SMS/month override <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <input type="number" name="sms_limit_monthly" id="sms_limit_monthly" value="{{ old('sms_limit_monthly', $tenant->sms_limit_monthly) }}" min="0" placeholder="Use plan default">
                @error('sms_limit_monthly')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="donation_limit_total">Donation cap override <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <input type="number" name="donation_limit_total" id="donation_limit_total" value="{{ old('donation_limit_total', $tenant->donation_limit_total) }}" min="0" placeholder="Use plan default">
                @error('donation_limit_total')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group">
            <label class="field-label" for="archive_at">Archive on <span style="color: var(--text-dim); font-weight: 400;">(scheduled — leave blank for never)</span></label>
            <input type="date" name="archive_at" id="archive_at" value="{{ old('archive_at', $tenant->archive_at?->format('Y-m-d')) }}">
            @error('archive_at')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label style="display:flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $tenant->is_active ?? true) ? 'checked' : '' }}>
                <span style="font-size: 14px;">Active</span>
            </label>
        </div>

        <div style="display:flex; gap: 10px; margin-top: 8px;">
            <button type="submit" class="btn-primary" style="width:auto; padding: 11px 24px;">{{ $tenant->exists ? 'Save changes' : 'Create tenant' }}</button>
            <a href="{{ route('super.tenants.index') }}" style="padding: 11px 20px; color: var(--text-muted); border: 1px solid var(--border); border-radius: 6px; text-decoration: none; font-size: 14px;">Cancel</a>
        </div>
    </form>
</div>

<style>
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .section-heading { margin: 20px 0 12px; font-size: 14px; color: var(--text); font-weight: 500; padding-bottom: 6px; border-bottom: 1px solid var(--border); }
    .card input[type=text], .card input[type=email], .card input[type=url], .card input[type=password] { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; }
    .card input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
</style>
@endsection
