@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Change password</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">
    Update your own login password. You'll stay signed in on this browser after saving.
</p>

<div class="card" style="max-width: 560px;">
    <form method="POST" action="{{ route('account.password.update') }}">
        @csrf
        <div class="form-group">
            <label class="field-label" for="current_password">Current password</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password">
            @error('current_password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="field-label" for="password">New password</label>
            <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password">
            @error('password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="field-label" for="password_confirmation">Confirm new password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 11px 24px;">Save new password</button>
    </form>
</div>

<style>
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input[type=password] { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    .card input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }
</style>
@endsection
