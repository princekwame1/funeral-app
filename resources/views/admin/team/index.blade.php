@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Team</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">Admins and users belonging to <strong style="color: var(--text);">{{ $tenant?->name ?? 'this tenant' }}</strong>. Everyone here can log in and act on this tenant's data.</p>

@if (session('team_flash'))
    @php $tf = session('team_flash'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $tf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $tf['message'] }}</div>
    </div>
@endif

@can(\App\Support\Permissions::TEAM_CREATE)
<div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <div>
            <h3 style="margin: 0 0 2px; font-size: 16px;">Add a team member</h3>
            <p style="color: var(--text-muted); margin: 0; font-size: 13px;">They'll be able to sign in immediately with the email + password below.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.team.store') }}">
        @csrf
        <div class="team-grid">
            <div class="form-group">
                <label class="field-label" for="team_name">Full name</label>
                <input type="text" name="name" id="team_name" value="{{ old('name') }}" required maxlength="120" placeholder="e.g. Ama Boateng">
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="team_email">Email</label>
                <input type="email" name="email" id="team_email" value="{{ old('email') }}" required placeholder="ama@example.com">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="team_password">Temporary password</label>
                <input type="text" name="password" id="team_password" value="{{ old('password') }}" required minlength="8" placeholder="min 8 characters">
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="team_role">Role</label>
                <select name="role" id="team_role" required>
                    <option value="admin" {{ old('role', 'admin') === 'admin' ? 'selected' : '' }}>Admin — full tenant access</option>
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User — read-only</option>
                </select>
                @error('role')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 24px;">+ Add to team</button>
    </form>
</div>
@endcan

<div class="card">
    <h3 style="margin: 0 0 14px; font-size: 15px;">Current team ({{ $users->total() }})</h3>
    @if ($users->count() === 0)
        <div class="empty">No team members yet.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td style="font-weight: 500;">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>
                            <span class="badge {{ $u->role === 'admin' ? 'badge-paid' : ($u->role === 'super' ? 'badge-failed' : 'badge-pending') }}">
                                {{ ucfirst($u->role) }}
                            </span>
                        </td>
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $u->created_at->format('d M Y') }}</td>
                        <td>
                            @if ($u->id === auth()->id())
                                <span style="color: var(--text-dim); font-size: 12px;">(you)</span>
                            @elseif ($u->isSuper())
                                <span style="color: var(--text-dim); font-size: 12px;">(super)</span>
                            @else
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    @can(\App\Support\Permissions::TEAM_RESET_PASSWORD)
                                        <button type="button" class="btn-verify" onclick="document.getElementById('pwd-{{ $u->id }}').classList.toggle('open')">Reset password</button>
                                    @endcan
                                    @can(\App\Support\Permissions::TEAM_DELETE)
                                        <form method="POST" action="{{ route('admin.team.destroy', $u->id) }}" style="margin: 0;"
                                              data-confirm="Remove {{ $u->name }} from the team? They will lose access immediately."
                                              data-confirm-title="Remove team member?"
                                              data-confirm-icon="warning"
                                              data-confirm-text="Remove"
                                              data-confirm-danger="1">
                                            @csrf
                                            <button type="submit" class="btn-verify" style="color: var(--red);">Remove</button>
                                        </form>
                                    @endcan
                                </div>
                            @endif
                        </td>
                    </tr>
                    @can(\App\Support\Permissions::TEAM_RESET_PASSWORD)
                        @if ($u->id !== auth()->id() && ! $u->isSuper())
                            <tr id="pwd-{{ $u->id }}" class="pwd-row">
                                <td colspan="5" style="background: var(--surface-2); padding: 12px 14px;">
                                    <form method="POST" action="{{ route('admin.team.reset-password', $u->id) }}"
                                          style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 0;"
                                          data-confirm="Reset {{ $u->name }}'s password to what you typed? Share it with them privately."
                                          data-confirm-title="Reset password?"
                                          data-confirm-icon="warning"
                                          data-confirm-text="Reset">
                                        @csrf
                                        <label style="font-size: 12px; color: var(--text-muted); min-width: 110px;">New password for<br><strong style="color: var(--text);">{{ $u->name }}</strong></label>
                                        <input type="text" name="password" required minlength="8" maxlength="120" placeholder="min 8 characters" style="flex: 1 1 260px; min-width: 260px; padding: 8px 12px; border: 1px solid var(--border); background: var(--surface); color: var(--text); border-radius: 6px; font-family: monospace;">
                                        <button type="submit" class="btn-primary" style="width: auto; padding: 8px 18px;">Save</button>
                                        <button type="button" class="btn-verify" onclick="document.getElementById('pwd-{{ $u->id }}').classList.remove('open')">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endcan
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $users->links() }}</div>
    @endif
</div>

<style>
    .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 14px; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input, .card select { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    .card input:focus, .card select:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; }
    .btn-verify:hover { border-color: var(--red); background: rgba(var(--red-rgb),0.08); }
    .pwd-row { display: none; }
    .pwd-row.open { display: table-row; }
</style>

<script>
    window.__tourKey = 'team-v1';
    window.__tourSteps = [
        {
            target: '.card',
            position: 'bottom',
            title: 'Your team',
            body: 'Everyone here belongs to this tenant. You can invite <strong>admins</strong> (full access) or <strong>users</strong> (read-only). To create super admins or move people between tenants, contact a super admin.',
        },
    ];
</script>
@endsection
