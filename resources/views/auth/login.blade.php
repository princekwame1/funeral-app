@extends('layouts.app')

@section('content')
@php
    $tenant = app(\App\Support\CurrentTenant::class)->get();
    $brandName = $tenant?->name ?: 'Funeral Donations';
    $brandLogo = $tenant?->logo_url;
    $initials = collect(explode(' ', trim($brandName)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->join('');
    if ($initials === '') { $initials = 'FD'; }
@endphp
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo-wrap">
            <div class="login-logo">
                @if ($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}">
                @else
                    {{ $initials }}
                @endif
            </div>
        </div>
        <h1>{{ $brandName }}</h1>
        <p>Sign in to manage donations, SMS and reports.</p>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Email" required autofocus>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Password" required>
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn-primary">Sign in</button>
        </form>
    </div>
</div>
@endsection
