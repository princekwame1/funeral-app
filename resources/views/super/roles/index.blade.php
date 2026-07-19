@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Roles & Permissions</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">
    Tick permissions to grant, untick to revoke. Changes apply immediately after Save.
    Super role is fixed — it always has everything.
</p>

@if (session('super_flash'))
    @php $sf = session('super_flash'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $sf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $sf['message'] }}</div>
    </div>
@endif

<div class="role-cards">
    @foreach ($roleMap as $role => $perms)
        <div class="role-card">
            <div class="role-header">
                <span class="role-dot" style="background: {{ \App\Support\Permissions::roleColor($role) }};"></span>
                <div>
                    <div class="role-name">{{ ucfirst($role) }}</div>
                    <div class="role-count"><span data-count-role="{{ $role }}">{{ count($perms) }}</span> permission(s)</div>
                </div>
                @if ($role === 'super')
                    <span class="badge badge-failed" style="margin-left: auto;">Locked</span>
                @endif
            </div>
            <div class="role-descr">
                @if ($role === 'super')
                    Full platform access. Manages tenants, users and branding across everything.
                @elseif ($role === 'admin')
                    Runs a single tenant. Takes donations, sends SMS, manages team.
                @elseif ($role === 'user')
                    Signs in and takes donations. Sees history but no SMS or team management.
                @endif
            </div>
        </div>
    @endforeach
</div>

<form method="POST" action="" id="rolesForm">
    @csrf
    <div class="card" style="padding: 0; overflow: hidden;">
        <table class="perm-table">
            <thead>
                <tr>
                    <th style="min-width: 340px;">Module / Permission</th>
                    @foreach (array_keys($roleMap) as $role)
                        <th style="text-align: center; min-width: 130px;">
                            <div class="role-th">
                                <span class="role-dot" style="background: {{ \App\Support\Permissions::roleColor($role) }};"></span>
                                {{ ucfirst($role) }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($modules as $moduleName => $perms)
                    <tr class="module-header">
                        <td>{{ $moduleName }}</td>
                        @foreach (array_keys($roleMap) as $role)
                            <td style="text-align: center;">
                                @if ($canEdit && $role !== 'super')
                                    <button type="button" class="module-toggle" data-role="{{ $role }}" data-module="{{ md5($moduleName) }}" title="Toggle all in this module">All</button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @foreach ($perms as $perm)
                        <tr data-perm-row="{{ $perm }}">
                            <td>
                                <div class="perm-slug">{{ $perm }}</div>
                                <div class="perm-desc">{{ \App\Support\Permissions::DESCRIPTIONS[$perm] ?? '' }}</div>
                            </td>
                            @foreach ($roleMap as $role => $rolePerms)
                                <td style="text-align: center;">
                                    @if ($role === 'super')
                                        <span class="check-locked" title="Super always has full access">&#10003;</span>
                                    @else
                                        <label class="perm-cell" data-role="{{ $role }}" data-module="{{ md5($moduleName) }}">
                                            <input type="checkbox"
                                                   name="permissions[{{ $role }}][]"
                                                   value="{{ $perm }}"
                                                   {{ in_array($perm, $rolePerms) ? 'checked' : '' }}
                                                   {{ ! $canEdit ? 'disabled' : '' }}
                                                   data-role="{{ $role }}"
                                                   data-perm="{{ $perm }}">
                                        </label>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canEdit)
        <div class="role-save-bar">
            @foreach (array_keys($roleMap) as $role)
                @if ($role !== 'super')
                    <div class="role-save-group">
                        <span class="role-save-label">
                            <span class="role-dot" style="background: {{ \App\Support\Permissions::roleColor($role) }};"></span>
                            {{ ucfirst($role) }} role
                        </span>
                        <button type="button" class="btn-primary role-save-btn" data-role-save="{{ $role }}" style="width: auto; padding: 9px 20px;">Save {{ $role }}</button>
                        <button type="button" class="btn-ghost role-reset-btn" data-role-reset="{{ $role }}">Reset to defaults</button>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</form>

<style>
    .role-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 16px; }
    .role-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px; }
    .role-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .role-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
    .role-name { font-size: 16px; font-weight: 600; color: var(--text); text-transform: capitalize; }
    .role-count { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .role-descr { font-size: 13px; color: var(--text-muted); line-height: 1.55; }

    .perm-table { border-collapse: collapse; width: 100%; }
    .perm-table th, .perm-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: top; }
    .perm-table th { background: var(--surface-2); color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; font-weight: 600; text-align: left; position: sticky; top: 0; z-index: 2; }
    .role-th { display: inline-flex; align-items: center; gap: 6px; }
    .module-header td { background: rgba(255,255,255,0.02); color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; padding: 8px 14px; }
    .perm-slug { font-family: 'SF Mono', 'Monaco', 'Consolas', monospace; font-size: 12px; color: var(--text); }
    .perm-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .check-locked { color: #ffb300; font-weight: 600; font-size: 15px; opacity: 0.7; }
    .perm-cell { display: inline-flex; }
    .module-toggle { background: transparent; color: var(--text-dim); border: 1px solid var(--border); border-radius: 999px; padding: 2px 10px; font-size: 10px; font-weight: 600; letter-spacing: 0.4px; cursor: pointer; text-transform: uppercase; }
    .module-toggle:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }

    .role-save-bar { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px; padding: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; }
    .role-save-group { display: flex; gap: 10px; align-items: center; }
    .role-save-label { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); }
    .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); padding: 9px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-ghost:hover { color: var(--text); border-color: var(--red); }
</style>

@if ($canEdit)
<script>
    (function () {
        var form = document.getElementById('rolesForm');
        var csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');

        function updateCount(role) {
            var checked = form.querySelectorAll('input[type=checkbox][data-role="' + role + '"]:checked').length;
            var badge = document.querySelector('[data-count-role="' + role + '"]');
            if (badge) badge.textContent = checked;
        }

        form.querySelectorAll('input[type=checkbox][data-role]').forEach(function (cb) {
            cb.addEventListener('change', function () { updateCount(cb.dataset.role); });
        });

        // "All" toggle at the top of each module
        form.querySelectorAll('.module-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var role = btn.dataset.role;
                var moduleId = btn.dataset.module;
                var cells = form.querySelectorAll('.perm-cell[data-role="' + role + '"][data-module="' + moduleId + '"] input[type=checkbox]');
                if (!cells.length) return;
                // If any is unchecked, check them all. Otherwise uncheck all.
                var anyUnchecked = Array.prototype.some.call(cells, function (c) { return !c.checked; });
                cells.forEach(function (c) { c.checked = anyUnchecked; });
                updateCount(role);
            });
        });

        function submitTo(action, role) {
            // Build a temporary form so we only submit the selected role's checkboxes.
            var tmp = document.createElement('form');
            tmp.method = 'POST';
            tmp.action = action;
            tmp.style.display = 'none';

            var csrfInput = document.createElement('input');
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            tmp.appendChild(csrfInput);

            if (role) {
                form.querySelectorAll('input[type=checkbox][data-role="' + role + '"]:checked').forEach(function (cb) {
                    var input = document.createElement('input');
                    input.name = 'permissions[]';
                    input.value = cb.value;
                    tmp.appendChild(input);
                });
            }

            document.body.appendChild(tmp);
            tmp.submit();
        }

        form.querySelectorAll('[data-role-save]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var role = btn.dataset.roleSave;
                var count = form.querySelectorAll('input[type=checkbox][data-role="' + role + '"]:checked').length;
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Save ' + role + ' permissions?',
                        body: 'The ' + role + ' role will have ' + count + ' permission(s) after this change.',
                        confirmText: 'Save',
                    }).then(function (r) {
                        if (r.isConfirmed) submitTo(@json(route('super.roles.update', ':role')).replace(':role', role), role);
                    });
                } else {
                    submitTo(@json(route('super.roles.update', ':role')).replace(':role', role), role);
                }
            });
        });

        form.querySelectorAll('[data-role-reset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var role = btn.dataset.roleReset;
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Reset ' + role + ' to defaults?',
                        body: 'Any custom permissions you set for the ' + role + ' role will be replaced with the built-in defaults.',
                        confirmText: 'Reset',
                        danger: true,
                    }).then(function (r) {
                        if (r.isConfirmed) submitTo(@json(route('super.roles.reset', ':role')).replace(':role', role), null);
                    });
                } else {
                    submitTo(@json(route('super.roles.reset', ':role')).replace(':role', role), null);
                }
            });
        });
    })();
</script>
@endif
@endsection
