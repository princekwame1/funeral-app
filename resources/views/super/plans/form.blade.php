@php $p = $plan; @endphp
<div class="plan-form-grid">
    <div class="form-group">
        <label class="field-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $p?->name) }}" required maxlength="100" placeholder="e.g. Starter">
        @error('name')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="field-label">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $p?->slug) }}" maxlength="40" placeholder="auto-generated" pattern="[a-z0-9-]+">
        @error('slug')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group plan-form-wide">
        <label class="field-label">Tagline</label>
        <input type="text" name="tagline" value="{{ old('tagline', $p?->tagline) }}" maxlength="200" placeholder="One-line description shown in the plan card">
        @error('tagline')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="field-label">SMS / month <span style="color: var(--text-dim); font-weight: 400;">(blank = unlimited)</span></label>
        <input type="number" name="sms_monthly" value="{{ old('sms_monthly', $p?->sms_monthly) }}" min="0" placeholder="Unlimited">
        @error('sms_monthly')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="field-label">Donations lifetime <span style="color: var(--text-dim); font-weight: 400;">(blank = unlimited)</span></label>
        <input type="number" name="donations_total" value="{{ old('donations_total', $p?->donations_total) }}" min="0" placeholder="Unlimited">
        @error('donations_total')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="field-label">Price (GHS)</label>
        <input type="number" name="price_ghs" value="{{ old('price_ghs', $p?->price_ghs ?? 0) }}" min="0" placeholder="0">
        @error('price_ghs')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="field-label">Sort order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $p?->sort_order ?? 0) }}" min="0">
        @error('sort_order')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label style="display: flex; align-items: center; gap: 10px; margin-top: 26px; cursor: pointer;">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $p?->is_active ?? true) ? 'checked' : '' }}>
            <span style="font-size: 14px; color: var(--text);">Active (visible to super admins when picking a plan)</span>
        </label>
    </div>
</div>

<style>
    .plan-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    .plan-form-wide { grid-column: 1 / -1; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .plan-form-grid input[type=text], .plan-form-grid input[type=number] { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; }
    .plan-form-grid input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }
</style>
