@php
    $tenant = app(\App\Support\CurrentTenant::class)->get();
    $brandPrimary = $tenant?->brand_primary ?: '#D32F2F';
    $brandAccent = $tenant?->brand_accent ?: '#9A0007';
    $brandName = $tenant?->name ?: 'Funeral Donations';
    $brandLogo = $tenant?->logo_url;
    $brandFavicon = $tenant?->favicon_url;
    $brandBackground = $tenant?->background_image_url;

    $hexToRgb = function ($hex) {
        $hex = ltrim($hex ?: '', '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '211, 47, 47';
        }
        return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
    };
    $brandPrimaryRgb = $hexToRgb($brandPrimary);
    $brandAccentRgb = $hexToRgb($brandAccent);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($brandFavicon)
        <link rel="icon" type="image/png" href="{{ $brandFavicon }}">
    @endif
    <title>{{ $title ?? $brandName }}</title>
    <style>
        :root {
            --red: {{ $brandPrimary }};
            --dark-red: {{ $brandAccent }};
            --red-rgb: {{ $brandPrimaryRgb }};
            --dark-red-rgb: {{ $brandAccentRgb }};
            --brand-tint-08: rgba({{ $brandPrimaryRgb }}, 0.08);
            --brand-tint-12: rgba({{ $brandPrimaryRgb }}, 0.12);
            --brand-tint-15: rgba({{ $brandPrimaryRgb }}, 0.15);
            --brand-tint-25: rgba({{ $brandPrimaryRgb }}, 0.25);
            --brand-tint-35: rgba({{ $brandPrimaryRgb }}, 0.35);
            --brand-tint-50: rgba({{ $brandPrimaryRgb }}, 0.5);
            --black: #000000;
            --surface: #121212;
            --surface-2: #1c1c1c;
            --border: rgba(255,255,255,0.12);
            --text: #ffffff;
            --text-muted: rgba(255,255,255,0.7);
            --text-dim: rgba(255,255,255,0.5);
        }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; background: var(--black); color: var(--text); }

        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 240px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 20px 16px; z-index: 40; }
        .sidebar .brand { font-weight: 600; letter-spacing: 0.5px; font-size: 16px; padding: 4px 8px 20px; border-bottom: 1px solid var(--border); }
        .sidebar .brand .accent { color: var(--red); }
        .sidebar nav { display: flex; flex-direction: column; gap: 4px; margin-top: 16px; flex: 1; overflow-y: auto; }
        .sidebar nav a { color: var(--text-muted); text-decoration: none; padding: 10px 12px; border-radius: 6px; font-size: 14px; border: 1px solid transparent; transition: color 0.15s, background 0.15s; }
        .sidebar nav a:hover { color: var(--text); background: rgba(255,255,255,0.06); }
        .sidebar nav a.active { color: var(--text); background: rgba(var(--red-rgb),0.15); border-color: rgba(var(--red-rgb),0.35); }
        .sidebar .nav-group-label { font-size: 11px; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-dim); padding: 14px 12px 4px; }
        .sidebar .nav-parent { display: flex; align-items: center; justify-content: space-between; width: 100%; background: transparent; border: 1px solid transparent; color: var(--text-muted); font-family: inherit; font-size: 14px; padding: 10px 12px; border-radius: 6px; cursor: pointer; text-align: left; transition: color 0.15s, background 0.15s; }
        .sidebar .nav-parent:hover { color: var(--text); background: rgba(255,255,255,0.06); }
        .sidebar .nav-parent.active-parent { color: var(--text); }
        .sidebar .nav-parent .chevron { font-size: 10px; transition: transform 0.15s; color: var(--text-dim); }
        .sidebar .nav-parent.open .chevron { transform: rotate(180deg); }
        .sidebar .nav-children { display: none; flex-direction: column; gap: 2px; padding-left: 12px; margin: 4px 0 6px; border-left: 1px solid var(--border); margin-left: 12px; }
        .sidebar .nav-children.open { display: flex; }
        .sidebar .nav-children a { font-size: 13px; padding: 8px 12px; }
        .topbar { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; gap: 12px; padding: 10px 24px; background: var(--surface); border-bottom: 1px solid var(--border); margin-left: 240px; min-height: 56px; }
        body:not(.is-authed) main { margin-left: 0 !important; padding: 0 !important; max-width: none !important; }
        .topbar .page-title { font-size: 15px; font-weight: 500; color: var(--text-muted); flex: 1; }
        .topbar .brand-mobile { display: none; font-weight: 600; font-size: 15px; flex: 1; }
        .topbar .brand-mobile .accent { color: var(--red); }
        .sidebar-toggle { display: none; background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 16px; line-height: 1; }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 35; }

        .topbar-tour-btn { display: inline-flex; align-items: center; gap: 6px; background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 6px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; font-family: inherit; transition: color 0.15s, border-color 0.15s, background 0.15s; }
        .topbar-tour-btn:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }
        .topbar-tour-icon { font-size: 14px; line-height: 1; }
        @media (max-width: 700px) { .topbar-tour-label { display: none; } .topbar-tour-btn { padding: 6px 8px; } }

        .profile { position: relative; }
        .profile-btn { display: flex; align-items: center; gap: 10px; background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: 999px; padding: 4px 12px 4px 4px; cursor: pointer; font-size: 13px; transition: border-color 0.15s, background 0.15s; }
        .profile-btn:hover { border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
        .profile-btn .avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--red); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; letter-spacing: 0.5px; }
        .profile-btn .profile-name { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .profile-btn .caret { color: var(--text-dim); font-size: 10px; }
        .profile-menu { display: none; position: absolute; top: calc(100% + 8px); right: 0; min-width: 240px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.5); padding: 6px; z-index: 50; }
        .profile-menu.open { display: block; }
        .profile-menu .profile-header { padding: 12px 14px 10px; border-bottom: 1px solid var(--border); margin-bottom: 6px; }
        .profile-menu .profile-header .name { font-size: 14px; font-weight: 500; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .profile-menu .profile-header .email { font-size: 12px; color: var(--text-dim); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px; }
        .profile-menu a, .profile-menu button { display: block; width: 100%; text-align: left; padding: 9px 12px; background: transparent; border: none; color: var(--text-muted); text-decoration: none; font-size: 13px; border-radius: 6px; cursor: pointer; font-family: inherit; }
        .profile-menu a:hover, .profile-menu button:hover { background: rgba(255,255,255,0.06); color: var(--text); }
        .profile-menu .logout-btn { color: var(--red); }
        .profile-menu .logout-btn:hover { background: rgba(var(--red-rgb),0.12); color: var(--red); }
        .profile-menu form { margin: 0; }
        .profile-menu .tour-menu-btn { display: block; width: 100%; text-align: left; padding: 9px 12px; background: transparent; border: none; color: var(--text-muted); font-size: 13px; border-radius: 6px; cursor: pointer; font-family: inherit; }
        .profile-menu .tour-menu-btn:hover { background: rgba(255,255,255,0.06); color: var(--text); }

        main { padding: 24px 32px; margin-left: 240px; }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.2s ease; }
            body.sidebar-open .sidebar { transform: translateX(0); }
            body.sidebar-open .sidebar-backdrop { display: block; }
            .topbar { margin-left: 0; padding: 10px 14px; }
            .topbar .page-title { display: none; }
            .topbar .brand-mobile { display: block; }
            .sidebar-toggle { display: block; }
            .profile-btn .profile-name { display: none; }
            main { margin-left: 0; padding: 16px; }
        }
        h1, h2, h3 { color: var(--text); }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .stat { background: var(--surface); border: 1px solid var(--border); padding: 16px; border-radius: 10px; border-left: 3px solid var(--red); }
        .stat .label { font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
        .stat .value { font-size: 22px; font-weight: 600; margin-top: 4px; color: var(--text); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); }
        th { background: var(--surface-2); font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .badge { padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-paid { background: rgba(46, 125, 50, 0.2); color: #66bb6a; border: 1px solid rgba(102, 187, 106, 0.4); }
        .badge-pending { background: rgba(255, 179, 0, 0.15); color: #ffb300; border: 1px solid rgba(255, 179, 0, 0.4); }
        .badge-failed { background: rgba(var(--red-rgb), 0.2); color: var(--red); border: 1px solid rgba(var(--red-rgb), 0.5); }
        .badge-method-online { background: rgba(66, 165, 245, 0.15); color: #64b5f6; border: 1px solid rgba(100, 181, 246, 0.4); }
        .badge-method-offline { background: rgba(255,255,255,0.06); color: var(--text-muted); border: 1px solid var(--border); }
        .filters { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center; }
        .filters input, .filters select { padding: 8px 12px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; width: auto; min-width: 180px; }
        .filters input[type=text], .filters input[type=search] { flex: 1 1 240px; min-width: 240px; }
        .filters select { padding-right: 34px; background-position: right 12px center; background-size: 10px; }
        .filters input::placeholder { color: var(--text-dim); }
        .filters input:focus, .filters select:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
        .filters button { padding: 8px 16px; background: var(--red); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.15s; }
        .filters button:hover { background: var(--dark-red); }
        .filters button[type=submit] { display: none; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 10px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 6px; color: var(--text); text-decoration: none; font-size: 13px; }
        .pagination a:hover { border-color: var(--red); color: var(--red); }
        .pagination .active span { background: var(--red); color: #fff; border-color: var(--red); }
        .empty { text-align: center; padding: 40px; color: var(--text-dim); }
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
            /* Layered background: dark tint on top, then tenant's uploaded image if any, then the default phone_bg.png, then solid black at the bottom. */
            background:
                linear-gradient(to bottom, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0.75) 55%, rgba(0,0,0,0.9) 100%),
                @if ($brandBackground)
                url('{{ $brandBackground }}') center bottom / cover no-repeat,
                @endif
                url('{{ asset('images/phone_bg.png') }}') center bottom / cover no-repeat,
                var(--black);
        }
        .login-wrap::before {
            content: '';
            position: absolute;
            top: -40%;
            left: -20%;
            width: 70vmax;
            height: 70vmax;
            background: radial-gradient(circle at center, rgba(var(--red-rgb),0.28) 0%, rgba(var(--red-rgb),0.10) 35%, transparent 65%);
            filter: blur(30px);
            pointer-events: none;
            animation: login-glow-a 18s ease-in-out infinite alternate;
        }
        .login-wrap::after {
            content: '';
            position: absolute;
            bottom: -40%;
            right: -20%;
            width: 60vmax;
            height: 60vmax;
            background: radial-gradient(circle at center, rgba(var(--dark-red-rgb),0.22) 0%, rgba(var(--dark-red-rgb),0.08) 40%, transparent 70%);
            filter: blur(40px);
            pointer-events: none;
            animation: login-glow-b 22s ease-in-out infinite alternate;
        }
        @keyframes login-glow-a { from { transform: translate(0, 0); } to { transform: translate(8vw, 6vh); } }
        @keyframes login-glow-b { from { transform: translate(0, 0); } to { transform: translate(-6vw, -8vh); } }
        .login-card {
            position: relative;
            z-index: 1;
            background: linear-gradient(160deg, rgba(24,24,24,0.92) 0%, rgba(14,14,14,0.92) 100%);
            border: 1px solid rgba(255,255,255,0.08);
            padding: 40px 36px 36px;
            border-radius: 16px;
            width: 400px;
            max-width: 100%;
            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.55),
                0 4px 16px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.03) inset;
        }
        .login-logo-wrap { display: flex; justify-content: center; margin-bottom: 22px; }
        .login-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--red), var(--dark-red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 24px rgba(var(--red-rgb),0.35);
            overflow: hidden;
        }
        .login-logo img { width: 100%; height: 100%; object-fit: cover; }
        .login-card h1 { margin: 0 0 6px; font-size: 24px; font-weight: 700; color: var(--text); text-align: center; letter-spacing: -0.3px; }
        .login-card h1 .accent { color: var(--red); }
        .login-card p { margin: 0 0 24px; color: var(--text-muted); font-size: 13.5px; text-align: center; line-height: 1.5; }
        .login-footer { text-align: center; margin-top: 20px; font-size: 12px; color: var(--text-dim); }
        .login-footer strong { color: var(--text-muted); font-weight: 500; }
        .form-group { margin-bottom: 14px; }
        .form-group input { width: 100%; padding: 12px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; }
        .form-group input::placeholder { color: var(--text-dim); }
        .form-group input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
        .btn-primary { width: 100%; padding: 11px; background: var(--red); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 500; transition: background 0.15s; }
        .btn-primary:hover { background: var(--dark-red); }
        .error { color: #ff6b6b; font-size: 13px; margin-top: 6px; }

        /* --- Sweet Alert (native) --- */
        .swal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.15s ease; }
        .swal-backdrop.open { opacity: 1; }
        .swal-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px 28px 24px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 30px 80px rgba(0,0,0,0.6); transform: scale(0.92); opacity: 0; transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.15s ease; }
        .swal-backdrop.open .swal-card { transform: scale(1); opacity: 1; }
        .swal-icon { width: 68px; height: 68px; margin: 0 auto 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 700; }
        .swal-icon-success { background: rgba(102,187,106,0.15); color: #66bb6a; border: 2px solid rgba(102,187,106,0.4); }
        .swal-icon-error { background: rgba(var(--red-rgb),0.15); color: var(--red); border: 2px solid rgba(var(--red-rgb),0.4); }
        .swal-icon-warning { background: rgba(255,179,0,0.15); color: #ffb300; border: 2px solid rgba(255,179,0,0.4); }
        .swal-icon-info { background: rgba(100,181,246,0.15); color: #64b5f6; border: 2px solid rgba(100,181,246,0.4); }
        .swal-icon-question { background: rgba(255,255,255,0.06); color: var(--text-muted); border: 2px solid var(--border); }
        .swal-title { font-size: 20px; font-weight: 600; color: var(--text); margin: 0 0 8px; letter-spacing: -0.2px; }
        .swal-body { font-size: 14px; color: var(--text-muted); line-height: 1.55; margin: 0 0 22px; }
        .swal-buttons { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        .swal-btn { padding: 10px 22px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: 1px solid transparent; transition: all 0.15s; font-family: inherit; min-width: 100px; }
        .swal-btn-confirm { background: var(--red); color: #fff; }
        .swal-btn-confirm:hover { background: var(--dark-red); }
        .swal-btn-cancel { background: transparent; color: var(--text-muted); border-color: var(--border); }
        .swal-btn-cancel:hover { color: var(--text); border-color: var(--text-muted); }
        .swal-btn-confirm.danger { background: var(--red); }
        .swal-toast { position: fixed; top: 24px; right: 24px; z-index: 10001; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 12px 32px rgba(0,0,0,0.4); min-width: 260px; max-width: 400px; transform: translateX(120%); transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .swal-toast.open { transform: translateX(0); }
        .swal-toast .swal-toast-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .swal-toast.success { border-left: 3px solid #66bb6a; }
        .swal-toast.success .swal-toast-dot { background: #66bb6a; }
        .swal-toast.error { border-left: 3px solid var(--red); }
        .swal-toast.error .swal-toast-dot { background: var(--red); }
        .swal-toast.warning { border-left: 3px solid #ffb300; }
        .swal-toast.warning .swal-toast-dot { background: #ffb300; }
        .swal-toast.info { border-left: 3px solid #64b5f6; }
        .swal-toast.info .swal-toast-dot { background: #64b5f6; }
        .swal-toast-content { flex: 1; }
        .swal-toast-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .swal-toast-body { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .swal-toast-close { background: transparent; border: none; color: var(--text-dim); cursor: pointer; padding: 0 4px; font-size: 18px; line-height: 1; }
        .swal-toast-close:hover { color: var(--text); }

        /* --- Archived banner --- */
        .archived-banner { position: fixed; top: 0; left: 0; right: 0; z-index: 55; background: #7c5a00; color: #fff; font-size: 13px; padding: 10px 16px; border-bottom: 1px solid rgba(0,0,0,0.35); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
        .archived-inner { max-width: 1600px; margin: 0 auto; display: flex; align-items: center; gap: 10px; }
        .archived-dot { width: 8px; height: 8px; border-radius: 50%; background: #ffd54f; flex-shrink: 0; }
        body.is-archived .sidebar, body.is-archived .topbar { top: 44px; }
        body.is-archived main { padding-top: 66px; }

        /* --- Impersonation banner --- */
        .impersonation-banner { position: fixed; top: 0; left: 0; right: 0; z-index: 60; background: repeating-linear-gradient(-45deg, #b71c1c, #b71c1c 12px, #c62828 12px, #c62828 24px); color: #fff; font-size: 13px; line-height: 1.3; padding: 8px 16px; border-bottom: 1px solid rgba(0,0,0,0.35); box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
        .impersonation-inner { max-width: 1600px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .impersonation-text strong { display: inline-block; margin-right: 8px; }
        .impersonation-text span { opacity: 0.85; font-size: 12px; }
        .impersonation-exit { background: #fff; color: #b71c1c; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .impersonation-exit:hover { background: rgba(255,255,255,0.9); }
        body.is-impersonating .sidebar,
        body.is-impersonating .topbar { top: 42px; }
        body.is-impersonating .sidebar { bottom: 0; }
        body.is-impersonating main { padding-top: 66px; }

        /* --- Tour --- */
        .tour-overlay { position: fixed; inset: 0; z-index: 9998; pointer-events: none; }
        .tour-mask { position: absolute; inset: 0; background: rgba(0,0,0,0.72); clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); transition: clip-path 0.25s ease; pointer-events: auto; }
        .tour-highlight { position: absolute; border: 2px solid var(--red); border-radius: 8px; box-shadow: 0 0 0 4px rgba(var(--red-rgb),0.25), 0 12px 40px rgba(0,0,0,0.6); pointer-events: none; transition: all 0.25s ease; z-index: 9998; }
        .tour-popover { position: absolute; z-index: 9999; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 18px 14px; width: 340px; max-width: calc(100vw - 32px); box-shadow: 0 24px 60px rgba(0,0,0,0.55); pointer-events: auto; transition: all 0.25s ease; }
        .tour-popover-title { font-size: 15px; font-weight: 600; color: var(--text); margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
        .tour-popover-title .tour-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--red); flex-shrink: 0; }
        .tour-popover-body { font-size: 13px; color: var(--text-muted); line-height: 1.55; margin: 0 0 14px; }
        .tour-popover-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .tour-popover-progress { font-size: 11px; color: var(--text-dim); letter-spacing: 0.4px; text-transform: uppercase; }
        .tour-popover-buttons { display: flex; gap: 6px; }
        .tour-btn { padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid transparent; transition: all 0.15s; font-family: inherit; }
        .tour-btn-primary { background: var(--red); color: #fff; }
        .tour-btn-primary:hover { background: var(--dark-red); }
        .tour-btn-secondary { background: transparent; color: var(--text-muted); border-color: var(--border); }
        .tour-btn-secondary:hover { color: var(--text); border-color: var(--text-muted); }
        .tour-btn-skip { background: transparent; color: var(--text-dim); }
        .tour-btn-skip:hover { color: var(--red); }

        /* --- Custom select (applied globally) --- */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 100%;
            padding: 11px 40px 11px 14px;
            border: 1.5px solid var(--border);
            background-color: var(--surface-2);
            color: var(--text);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            line-height: 1.35;
            cursor: pointer;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23bdbdbd' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 12px;
            transition: border-color 0.15s, box-shadow 0.15s, background-color 0.15s;
        }
        select:hover { border-color: rgba(var(--red-rgb), 0.5); }
        select:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }
        select:disabled { opacity: 0.5; cursor: not-allowed; }
        /* Match native OS dropdown styling for the popup */
        select option { background: var(--surface-2); color: var(--text); padding: 8px 12px; }
        select option:checked { background: var(--red); color: #fff; }

        /* --- Custom radios & checkboxes (applied globally) --- */
        input[type="radio"], input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 18px;
            height: 18px;
            margin: 0;
            border: 1.5px solid var(--border);
            background: var(--surface-2);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
            vertical-align: middle;
        }
        input[type="radio"] { border-radius: 50%; }
        input[type="checkbox"] { border-radius: 4px; }
        input[type="radio"]:hover, input[type="checkbox"]:hover { border-color: rgba(var(--red-rgb), 0.6); }
        input[type="radio"]:focus-visible, input[type="checkbox"]:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.25); }
        input[type="radio"]:checked, input[type="checkbox"]:checked { background: var(--red); border-color: var(--red); }
        input[type="radio"]:checked::after {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #fff;
        }
        input[type="checkbox"]:checked::after {
            content: '';
            width: 5px;
            height: 9px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg) translate(-1px, -1px);
        }
        input[type="radio"]:disabled, input[type="checkbox"]:disabled { opacity: 0.5; cursor: not-allowed; }

        button.is-loading { opacity: 0.85; cursor: wait; pointer-events: none; position: relative; }
        button.is-loading .btn-label { visibility: hidden; }
        button.is-loading .btn-spinner { position: absolute; top: 50%; left: 50%; margin: -8px 0 0 -8px; }
        .btn-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.25); border-top-color: currentColor; border-radius: 50%; box-sizing: border-box; animation: btn-spin 0.75s linear infinite; will-change: transform; transform-origin: center; backface-visibility: hidden; }
        @keyframes btn-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        button.is-loading.spinner-inline .btn-label { visibility: visible; margin-left: 6px; }
        button.is-loading.spinner-inline .btn-spinner { position: static; margin: 0; vertical-align: -3px; }
    </style>
</head>
<body class="{{ auth()->check() ? 'is-authed' : '' }}{{ session('impersonator_id') ? ' is-impersonating' : '' }}{{ app(\App\Support\CurrentTenant::class)->get()?->isArchived() ? ' is-archived' : '' }}">
    @auth
    @php
        $impersonatorId = session('impersonator_id');
        $impersonator = $impersonatorId
            ? \App\Models\User::withoutGlobalScopes()->find($impersonatorId)
            : null;
        $pageTitle = 'Dashboard';
        if (request()->routeIs('admin.dashboard')) $pageTitle = 'Dashboard';
        elseif (request()->routeIs('admin.donations.*')) $pageTitle = 'Donations';
        elseif (request()->routeIs('admin.sms.notifications')) $pageTitle = 'SMS Notification';
        elseif (request()->routeIs('admin.sms.invitations')) $pageTitle = 'Funeral Invitation';
        elseif (request()->routeIs('admin.sms.post')) $pageTitle = 'Post Notification';
        elseif (request()->routeIs('admin.sms.logs')) $pageTitle = 'SMS Logs';
        elseif (request()->routeIs('admin.contacts.*')) $pageTitle = 'Contacts';
        elseif (request()->routeIs('admin.contact-groups.*')) $pageTitle = 'Contact Groups';
        elseif (request()->routeIs('admin.team.*')) $pageTitle = 'Team';
        elseif (request()->routeIs('admin.events.*')) $pageTitle = 'Funeral';
        elseif (request()->routeIs('super.branding.*')) $pageTitle = 'Branding';
        elseif (request()->routeIs('super.overview')) $pageTitle = 'Super Overview';
        elseif (request()->routeIs('super.tenants.*')) $pageTitle = 'Tenants';
        elseif (request()->routeIs('super.users*')) $pageTitle = 'Users';
        elseif (request()->routeIs('super.roles.*')) $pageTitle = 'Roles & Permissions';
        elseif (request()->routeIs('super.plans.*')) $pageTitle = 'Plans';
        elseif (request()->routeIs('super.webhooks.*')) $pageTitle = 'Webhooks';
        $user = auth()->user();
        $initials = collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->join('');
        if ($initials === '') { $initials = mb_strtoupper(mb_substr($user->email, 0, 1)); }
    @endphp

    @php
        $smsGroupOpen = request()->routeIs('admin.sms.*');
    @endphp
    @if ($tenant?->isArchived())
        <div class="archived-banner">
            <div class="archived-inner">
                <span class="archived-dot"></span>
                <div>
                    <strong>Archive mode.</strong>
                    This tenant was archived on {{ $tenant->archived_at->format('d M Y') }} — the app is <strong>read-only</strong>.
                    @can(\App\Support\Permissions::TENANTS_EDIT)
                        <a href="{{ route('super.tenants.index') }}" style="color: inherit; text-decoration: underline; margin-left: 6px;">Unarchive</a>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    @if ($impersonator)
        <div class="impersonation-banner">
            <div class="impersonation-inner">
                <div class="impersonation-text">
                    <strong>Impersonating {{ $user->name }}</strong>
                    <span>({{ $user->email }} · role: {{ $user->role }}) — original account: {{ $impersonator->name }}</span>
                </div>
                <form method="POST" action="{{ route('impersonate.stop') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="impersonation-exit">Stop impersonation</button>
                </form>
            </div>
        </div>
    @endif
    <aside class="sidebar" data-tour="sidebar">
        <div class="brand" style="display:flex; align-items: center; gap: 10px;">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="" style="width: 28px; height: 28px; border-radius: 6px; object-fit: cover;">
            @endif
            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $brandName }} <span class="accent">·</span> Admin</div>
        </div>
        <nav>
            @can(\App\Support\Permissions::DASHBOARD_VIEW)
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            @endcan
            @can(\App\Support\Permissions::DONATIONS_VIEW)
                <a href="{{ route('admin.donations.index') }}" class="{{ request()->routeIs('admin.donations.*') ? 'active' : '' }}">Donations</a>
            @endcan

            @canany([
                \App\Support\Permissions::SMS_NOTIFICATIONS_VIEW,
                \App\Support\Permissions::SMS_INVITATIONS_VIEW,
                \App\Support\Permissions::SMS_POST_VIEW,
                \App\Support\Permissions::SMS_LOGS_VIEW,
                \App\Support\Permissions::CONTACTS_VIEW,
            ])
                <button type="button" class="nav-parent {{ $smsGroupOpen ? 'open active-parent' : '' }}" data-nav-toggle="smsGroup">
                    <span>SMS Notification</span>
                    <span class="chevron">&#9662;</span>
                </button>
                <div id="smsGroup" class="nav-children {{ $smsGroupOpen ? 'open' : '' }}">
                    @can(\App\Support\Permissions::SMS_NOTIFICATIONS_VIEW)
                        <a href="{{ route('admin.sms.notifications') }}" class="{{ request()->routeIs('admin.sms.notifications') ? 'active' : '' }}">General notifications</a>
                    @endcan
                    @can(\App\Support\Permissions::SMS_INVITATIONS_VIEW)
                        <a href="{{ route('admin.sms.invitations') }}" class="{{ request()->routeIs('admin.sms.invitations') ? 'active' : '' }}">Funeral Invitation</a>
                    @endcan
                    @can(\App\Support\Permissions::SMS_POST_VIEW)
                        <a href="{{ route('admin.sms.post') }}" class="{{ request()->routeIs('admin.sms.post') ? 'active' : '' }}">Post Notification</a>
                    @endcan
                    @can(\App\Support\Permissions::SMS_LOGS_VIEW)
                        <a href="{{ route('admin.sms.logs') }}" class="{{ request()->routeIs('admin.sms.logs') ? 'active' : '' }}">SMS Logs</a>
                    @endcan
                    @can(\App\Support\Permissions::CONTACTS_VIEW)
                        <a href="{{ route('admin.contacts.index') }}" class="{{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">Contacts</a>
                        <a href="{{ route('admin.contact-groups.index') }}" class="{{ request()->routeIs('admin.contact-groups.*') ? 'active' : '' }}">Contact Groups</a>
                    @endcan
                </div>
            @endcanany

            @can(\App\Support\Permissions::EVENTS_VIEW)
                <a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}">Funeral</a>
            @endcan
            @can(\App\Support\Permissions::TEAM_VIEW)
                <a href="{{ route('admin.team.index') }}" class="{{ request()->routeIs('admin.team.*') ? 'active' : '' }}">Team</a>
            @endcan

            @canany([
                \App\Support\Permissions::PLATFORM_OVERVIEW,
                \App\Support\Permissions::TENANTS_VIEW,
                \App\Support\Permissions::BRANDING_VIEW,
                \App\Support\Permissions::USERS_VIEW,
                \App\Support\Permissions::ROLES_VIEW,
                \App\Support\Permissions::PLANS_MANAGE,
                \App\Support\Permissions::WEBHOOKS_VIEW,
            ])
                <div class="nav-group-label">Super Admin</div>
                @can(\App\Support\Permissions::PLATFORM_OVERVIEW)
                    <a href="{{ route('super.overview') }}" class="{{ request()->routeIs('super.overview') ? 'active' : '' }}">Overview</a>
                @endcan
                @can(\App\Support\Permissions::TENANTS_VIEW)
                    <a href="{{ route('super.tenants.index') }}" class="{{ request()->routeIs('super.tenants.*') ? 'active' : '' }}">Tenants</a>
                @endcan
                @can(\App\Support\Permissions::BRANDING_VIEW)
                    <a href="{{ route('super.branding.edit') }}" class="{{ request()->routeIs('super.branding.*') ? 'active' : '' }}">Branding</a>
                @endcan
                @can(\App\Support\Permissions::USERS_VIEW)
                    <a href="{{ route('super.users') }}" class="{{ request()->routeIs('super.users*') ? 'active' : '' }}">Users</a>
                @endcan
                @can(\App\Support\Permissions::ROLES_VIEW)
                    <a href="{{ route('super.roles.index') }}" class="{{ request()->routeIs('super.roles.*') ? 'active' : '' }}">Roles & Permissions</a>
                @endcan
                @can(\App\Support\Permissions::PLANS_MANAGE)
                    <a href="{{ route('super.plans.index') }}" class="{{ request()->routeIs('super.plans.*') ? 'active' : '' }}">Plans</a>
                @endcan
                @can(\App\Support\Permissions::WEBHOOKS_VIEW)
                    <a href="{{ route('super.webhooks.index') }}" class="{{ request()->routeIs('super.webhooks.*') ? 'active' : '' }}">Webhooks</a>
                @endcan
                @if (session('super.active_tenant'))
                    <form method="POST" action="{{ route('super.tenants.clear-switch') }}" style="margin-top: 8px;">
                        @csrf
                        <button type="submit" style="width:100%; background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-muted); padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">Exit tenant view</button>
                    </form>
                @endif
            @endcanany
        </nav>
    </aside>
    <div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

    <div class="topbar">
        <button type="button" class="sidebar-toggle" aria-label="Toggle navigation" onclick="document.body.classList.toggle('sidebar-open')">&#9776;</button>
        <div class="brand-mobile">Funeral Donations <span class="accent">·</span> Admin</div>
        <div class="page-title">{{ $pageTitle }}</div>
        <button type="button" class="topbar-tour-btn" onclick="if(window.FuneralTour) window.FuneralTour.start(true);" title="Guided tour of this page">
            <span class="topbar-tour-icon">&#9432;</span>
            <span class="topbar-tour-label">Take a tour</span>
        </button>
        <div class="profile" id="profileMenu" data-tour="profile">
            <button type="button" class="profile-btn" onclick="document.getElementById('profileMenu').querySelector('.profile-menu').classList.toggle('open')">
                <span class="avatar">{{ $initials }}</span>
                <span class="profile-name">{{ $user->name }}</span>
                <span class="caret">&#9662;</span>
            </button>
            <div class="profile-menu">
                <div class="profile-header">
                    <div class="name">{{ $user->name }}</div>
                    <div class="email">{{ $user->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">Sign out</button>
                </form>
            </div>
        </div>
    </div>
    @endauth
    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @php
        $flashPayload = null;
        foreach ([
            ['donation_result', 'title_key' => 'ok'],
            ['sms_result',      'title_key' => 'sent_or_failed'],
            ['sms_error',       'always_error' => true],
            ['super_flash',     'title_key' => 'ok'],
            ['branding_flash',  'title_key' => 'ok'],
            ['team_flash',      'title_key' => 'ok'],
            ['impersonation_flash', 'is_string' => true],
        ] as $entry) {
            $key = $entry[0];
            if (! session()->has($key)) continue;
            $v = session($key);
            if (! empty($entry['is_string'])) {
                $flashPayload = ['type' => 'info', 'title' => 'Impersonation', 'message' => (string) $v];
            } elseif (! empty($entry['always_error'])) {
                $flashPayload = ['type' => 'error', 'title' => 'Something went wrong', 'message' => (string) $v];
            } elseif (is_array($v)) {
                if ($key === 'sms_result') {
                    $ok = ($v['failed'] ?? 0) === 0 && ($v['sent'] ?? 0) > 0;
                    $flashPayload = [
                        'type' => $ok ? 'success' : (($v['failed'] ?? 0) > 0 ? 'warning' : 'info'),
                        'title' => $ok ? 'Campaign queued' : (($v['failed'] ?? 0) > 0 ? 'Sent with issues' : 'Nothing to send'),
                        'message' => "Sent: {$v['sent']} · Failed: {$v['failed']} · Skipped: {$v['skipped']}",
                    ];
                } else {
                    $flashPayload = [
                        'type' => ($v['ok'] ?? true) ? 'success' : 'error',
                        'title' => ($v['ok'] ?? true) ? 'Success' : 'Failed',
                        'message' => $v['message'] ?? '',
                    ];
                }
            }
            if ($flashPayload) break;
        }
    @endphp

    @if ($flashPayload)
        <script>
            window.__flash = @json($flashPayload);
        </script>
    @endif

    <script>
        // --- Sweet Alert (native, no dependency) ---
        (function () {
            function el(tag, attrs, children) {
                var e = document.createElement(tag);
                if (attrs) for (var k in attrs) {
                    if (k === 'className') e.className = attrs[k];
                    else if (k === 'html') e.innerHTML = attrs[k];
                    else e.setAttribute(k, attrs[k]);
                }
                if (children) children.forEach(function (c) { e.appendChild(c); });
                return e;
            }

            function fire(opts) {
                opts = opts || {};
                return new Promise(function (resolve) {
                    var backdrop = document.createElement('div');
                    backdrop.className = 'swal-backdrop';
                    var card = document.createElement('div');
                    card.className = 'swal-card';

                    var icon = opts.icon || 'question';
                    card.innerHTML =
                        '<div class="swal-icon swal-icon-' + icon + '">' + iconChar(icon) + '</div>' +
                        (opts.title ? '<div class="swal-title">' + escapeHtml(opts.title) + '</div>' : '') +
                        (opts.body ? '<div class="swal-body">' + (opts.html ? opts.body : escapeHtml(opts.body)) + '</div>' : '') +
                        '<div class="swal-buttons">' +
                            (opts.showCancel !== false ? '<button type="button" class="swal-btn swal-btn-cancel" data-a="cancel">' + (opts.cancelText || 'Cancel') + '</button>' : '') +
                            '<button type="button" class="swal-btn swal-btn-confirm ' + (opts.danger ? 'danger' : '') + '" data-a="confirm">' + (opts.confirmText || 'OK') + '</button>' +
                        '</div>';

                    backdrop.appendChild(card);
                    document.body.appendChild(backdrop);
                    document.body.style.overflow = 'hidden';

                    requestAnimationFrame(function () { backdrop.classList.add('open'); });

                    var confirmBtn = card.querySelector('[data-a="confirm"]');
                    confirmBtn && confirmBtn.focus();

                    function close(result) {
                        backdrop.classList.remove('open');
                        setTimeout(function () {
                            backdrop.remove();
                            document.body.style.overflow = '';
                            resolve(result);
                        }, 180);
                    }

                    card.querySelectorAll('[data-a]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            close({ isConfirmed: btn.dataset.a === 'confirm' });
                        });
                    });
                    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close({ isConfirmed: false }); });
                    document.addEventListener('keydown', function onKey(e) {
                        if (e.key === 'Escape') { close({ isConfirmed: false }); document.removeEventListener('keydown', onKey); }
                        else if (e.key === 'Enter') { close({ isConfirmed: true }); document.removeEventListener('keydown', onKey); }
                    });
                });
            }

            function toast(opts) {
                opts = opts || {};
                var type = opts.type || 'info';
                var t = document.createElement('div');
                t.className = 'swal-toast ' + type;
                t.innerHTML =
                    '<span class="swal-toast-dot"></span>' +
                    '<div class="swal-toast-content">' +
                        (opts.title ? '<div class="swal-toast-title">' + escapeHtml(opts.title) + '</div>' : '') +
                        (opts.body ? '<div class="swal-toast-body">' + escapeHtml(opts.body) + '</div>' : '') +
                    '</div>' +
                    '<button class="swal-toast-close" aria-label="Dismiss">&times;</button>';
                document.body.appendChild(t);
                requestAnimationFrame(function () { t.classList.add('open'); });
                var timer = setTimeout(dismiss, opts.duration || 4500);
                function dismiss() {
                    clearTimeout(timer);
                    t.classList.remove('open');
                    setTimeout(function () { t.remove(); }, 300);
                }
                t.querySelector('.swal-toast-close').addEventListener('click', dismiss);
                return { dismiss: dismiss };
            }

            function iconChar(icon) {
                switch (icon) {
                    case 'success': return '&#10003;';
                    case 'error': return '&#10005;';
                    case 'warning': return '&#33;';
                    case 'question': return '&#63;';
                    case 'info':
                    default: return '&#8505;';
                }
            }

            function escapeHtml(s) {
                var d = document.createElement('div');
                d.textContent = s || '';
                return d.innerHTML;
            }

            window.Swal = { fire: fire, toast: toast };

            // Intercept forms that ask for confirmation via a native onclick="return confirm('...')"
            // Also intercept any form with data-confirm="..." to show the Swal instead.
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.dataset.swalHandled === '1') return;

                var confirmMsg = form.dataset.confirm;
                if (!confirmMsg) return;

                e.preventDefault();
                fire({
                    icon: form.dataset.confirmIcon || 'warning',
                    title: form.dataset.confirmTitle || 'Are you sure?',
                    body: confirmMsg,
                    confirmText: form.dataset.confirmText || 'Yes',
                    cancelText: form.dataset.cancelText || 'Cancel',
                    danger: form.dataset.confirmDanger === '1',
                }).then(function (r) {
                    if (r.isConfirmed) {
                        form.dataset.swalHandled = '1';
                        form.submit();
                    }
                });
            }, true);

            // Also intercept confirm() on links/buttons using data-swal-confirm
            document.addEventListener('click', function (e) {
                var t = e.target.closest('[data-swal-confirm]');
                if (!t) return;
                if (t.dataset.swalHandledClick === '1') { t.dataset.swalHandledClick = ''; return; }
                e.preventDefault();
                fire({
                    icon: t.dataset.confirmIcon || 'warning',
                    title: t.dataset.confirmTitle || 'Are you sure?',
                    body: t.dataset.swalConfirm,
                    confirmText: t.dataset.confirmText || 'Yes',
                    danger: t.dataset.confirmDanger === '1',
                }).then(function (r) {
                    if (r.isConfirmed) {
                        t.dataset.swalHandledClick = '1';
                        t.click();
                    }
                });
            }, true);

            // Auto-fire toasts from server-side flash data
            document.addEventListener('DOMContentLoaded', function () {
                var flash = window.__flash;
                if (flash && flash.message) {
                    toast({ type: flash.type || 'info', title: flash.title || '', body: flash.message });
                }
            });
        })();

        // --- Auto-filter: any .filters form submits on select change / debounced text input ---
        (function () {
            document.querySelectorAll('form.filters').forEach(function (form) {
                if (form.dataset.autoFilter === 'off') return;

                form.querySelectorAll('select').forEach(function (sel) {
                    sel.addEventListener('change', function () {
                        form.dataset.swalHandled = '1'; // skip Swal confirmations
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    });
                });

                var debounceTimer = null;
                form.querySelectorAll('input[type=text], input[type=search], input[type=email], input[type=tel], input[type=number]').forEach(function (inp) {
                    inp.addEventListener('input', function () {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(function () {
                            form.dataset.swalHandled = '1';
                            form.requestSubmit ? form.requestSubmit() : form.submit();
                        }, 500);
                    });
                    // Cancel pending debounce on Enter (browser's native submit takes over immediately)
                    inp.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') clearTimeout(debounceTimer);
                    });
                });
            });
        })();

        (function () {
            // Loading state for form submit buttons
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.hasAttribute('data-no-loading')) return;
                var btns = form.querySelectorAll('button[type=submit], button:not([type])');
                btns.forEach(function (btn) {
                    if (btn.dataset.loading === '1' || btn.disabled) return;
                    btn.dataset.loading = '1';
                    // Wrap current content in .btn-label so we can hide it
                    var label = document.createElement('span');
                    label.className = 'btn-label';
                    while (btn.firstChild) label.appendChild(btn.firstChild);
                    btn.appendChild(label);
                    var spinner = document.createElement('span');
                    spinner.className = 'btn-spinner';
                    btn.appendChild(spinner);
                    btn.classList.add('is-loading');
                    // Delay disabling so form value/submit still goes through cleanly
                    setTimeout(function () { btn.disabled = true; }, 0);
                });
            });

            // Safety: if the browser navigates back to a bfcache'd page, reset any stuck loading buttons
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                document.querySelectorAll('button.is-loading').forEach(function (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    delete btn.dataset.loading;
                    var label = btn.querySelector('.btn-label');
                    var spinner = btn.querySelector('.btn-spinner');
                    if (spinner) spinner.remove();
                    if (label) {
                        while (label.firstChild) btn.insertBefore(label.firstChild, label);
                        label.remove();
                    }
                });
            });
        })();
    </script>

    @auth
    <script>
        // --- Funeral Donations Tour Engine ---
        (function () {
            var overlay, mask, highlight, popover, currentSteps = [], currentIndex = 0, currentKey = '';

            function getStorageKey(key) { return 'funeral-tour-done-' + key; }

            function ensureDom() {
                if (overlay) return;
                overlay = document.createElement('div');
                overlay.className = 'tour-overlay';
                mask = document.createElement('div');
                mask.className = 'tour-mask';
                highlight = document.createElement('div');
                highlight.className = 'tour-highlight';
                overlay.appendChild(mask);
                overlay.appendChild(highlight);
                popover = document.createElement('div');
                popover.className = 'tour-popover';
                overlay.appendChild(popover);
                document.body.appendChild(overlay);

                mask.addEventListener('click', end);
                document.addEventListener('keydown', onKey);
                window.addEventListener('resize', function () { render(); });
                window.addEventListener('scroll', function () { render(); }, { passive: true });
            }

            function onKey(e) {
                if (!overlay || overlay.style.display === 'none') return;
                if (e.key === 'Escape') { end(); }
                else if (e.key === 'ArrowRight' || e.key === 'Enter') { next(); }
                else if (e.key === 'ArrowLeft') { prev(); }
            }

            function render() {
                if (!currentSteps.length) return;
                var step = currentSteps[currentIndex];
                var target = step.target ? document.querySelector(step.target) : null;

                if (target) {
                    var rect = target.getBoundingClientRect();
                    var pad = 8;
                    var top = rect.top - pad, left = rect.left - pad;
                    var w = rect.width + pad * 2, h = rect.height + pad * 2;

                    highlight.style.display = 'block';
                    highlight.style.top = top + 'px';
                    highlight.style.left = left + 'px';
                    highlight.style.width = w + 'px';
                    highlight.style.height = h + 'px';

                    // Cut the highlight out of the mask using an evenodd polygon
                    mask.style.clipPath = 'polygon(' +
                        '0 0, 100% 0, 100% 100%, 0 100%, 0 0, ' +
                        left + 'px ' + top + 'px, ' +
                        (left + w) + 'px ' + top + 'px, ' +
                        (left + w) + 'px ' + (top + h) + 'px, ' +
                        left + 'px ' + (top + h) + 'px, ' +
                        left + 'px ' + top + 'px)';

                    // Scroll target into view if off-screen
                    if (rect.top < 60 || rect.bottom > window.innerHeight - 60) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    // Position popover
                    var popW = popover.offsetWidth || 340;
                    var popH = popover.offsetHeight || 160;
                    var pos = step.position || 'auto';
                    var top2, left2;

                    if (pos === 'auto') {
                        pos = (rect.bottom + popH + 20 < window.innerHeight) ? 'bottom' : 'top';
                    }

                    if (pos === 'bottom') {
                        top2 = rect.bottom + 16;
                        left2 = rect.left + rect.width / 2 - popW / 2;
                    } else if (pos === 'top') {
                        top2 = rect.top - popH - 16;
                        left2 = rect.left + rect.width / 2 - popW / 2;
                    } else if (pos === 'right') {
                        top2 = rect.top + rect.height / 2 - popH / 2;
                        left2 = rect.right + 16;
                    } else if (pos === 'left') {
                        top2 = rect.top + rect.height / 2 - popH / 2;
                        left2 = rect.left - popW - 16;
                    }

                    // Clamp within viewport
                    left2 = Math.max(12, Math.min(window.innerWidth - popW - 12, left2));
                    top2 = Math.max(12, Math.min(window.innerHeight - popH - 12, top2));

                    popover.style.top = top2 + 'px';
                    popover.style.left = left2 + 'px';
                } else {
                    // No target — center popover, hide highlight
                    highlight.style.display = 'none';
                    mask.style.clipPath = 'polygon(0 0, 100% 0, 100% 100%, 0 100%)';
                    popover.style.top = '50%';
                    popover.style.left = '50%';
                    popover.style.transform = 'translate(-50%, -50%)';
                    return;
                }

                popover.style.transform = '';
            }

            function renderPopover() {
                var step = currentSteps[currentIndex];
                var total = currentSteps.length;
                var isLast = currentIndex === total - 1;
                var isFirst = currentIndex === 0;

                popover.innerHTML =
                    '<div class="tour-popover-title"><span class="tour-dot"></span>' + escapeHtml(step.title || '') + '</div>' +
                    '<div class="tour-popover-body">' + (step.body || '') + '</div>' +
                    '<div class="tour-popover-footer">' +
                        '<span class="tour-popover-progress">Step ' + (currentIndex + 1) + ' of ' + total + '</span>' +
                        '<div class="tour-popover-buttons">' +
                            '<button type="button" class="tour-btn tour-btn-skip" data-tour-action="end">Skip</button>' +
                            (isFirst ? '' : '<button type="button" class="tour-btn tour-btn-secondary" data-tour-action="prev">Back</button>') +
                            '<button type="button" class="tour-btn tour-btn-primary" data-tour-action="' + (isLast ? 'end' : 'next') + '">' + (isLast ? 'Finish' : 'Next') + '</button>' +
                        '</div>' +
                    '</div>';

                popover.querySelectorAll('[data-tour-action]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var a = btn.dataset.tourAction;
                        if (a === 'next') next();
                        else if (a === 'prev') prev();
                        else end();
                    });
                });
            }

            function escapeHtml(s) {
                var d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function next() {
                if (currentIndex + 1 < currentSteps.length) {
                    currentIndex++;
                    renderPopover();
                    render();
                } else {
                    end();
                }
            }

            function prev() {
                if (currentIndex > 0) {
                    currentIndex--;
                    renderPopover();
                    render();
                }
            }

            function end() {
                if (overlay) overlay.style.display = 'none';
                if (currentKey) {
                    try { localStorage.setItem(getStorageKey(currentKey), '1'); } catch (e) {}
                }
                currentSteps = [];
                currentIndex = 0;
            }

            function start(force) {
                if (!window.__tourSteps || !window.__tourSteps.length) return;
                if (!force) {
                    try {
                        if (localStorage.getItem(getStorageKey(window.__tourKey || 'default'))) return;
                    } catch (e) {}
                }
                currentSteps = window.__tourSteps;
                currentIndex = 0;
                currentKey = window.__tourKey || 'default';
                ensureDom();
                overlay.style.display = 'block';
                renderPopover();
                setTimeout(render, 30);
            }

            window.FuneralTour = { start: start, end: end };

            // Auto-start on page load if steps are defined and not seen before
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () { start(false); }, 400);
            });
        })();

        (function () {
            document.addEventListener('click', function (e) {
                var wrap = document.getElementById('profileMenu');
                if (!wrap) return;
                var menu = wrap.querySelector('.profile-menu');
                if (!menu.classList.contains('open')) return;
                if (!wrap.contains(e.target)) menu.classList.remove('open');
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var menu = document.querySelector('#profileMenu .profile-menu.open');
                if (menu) menu.classList.remove('open');
            });

            document.querySelectorAll('.sidebar .nav-parent[data-nav-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = document.getElementById(btn.dataset.navToggle);
                    if (!target) return;
                    btn.classList.toggle('open');
                    target.classList.toggle('open');
                });
            });
        })();
    </script>
    @endauth
</body>
</html>
