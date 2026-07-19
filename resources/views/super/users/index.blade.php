@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Users</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">Every user across every tenant.</p>

@if (session('super_flash'))
    @php $sf = session('super_flash'); @endphp
    <div class="card" style="margin-bottom: 16px; border-left: 3px solid {{ $sf['ok'] ? '#66bb6a' : 'var(--red)' }};">
        <div style="font-weight: 500; font-size: 14px;">{{ $sf['message'] }}</div>
    </div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 12px; font-size: 15px;">Add user</h3>
    <form method="POST" action="{{ route('super.users.store') }}">
        @csrf
        <div class="grid-4">
            <div class="form-group">
                <label class="field-label">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label">Password</label>
                <input type="password" name="password" minlength="8" required>
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label">Role</label>
                <select name="role" required>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User</option>
                    <option value="super" {{ old('role') === 'super' ? 'selected' : '' }}>Super</option>
                </select>
                @error('role')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-group">
            <label class="field-label">Tenant <span style="color: var(--text-dim); font-weight: 400;">(leave blank for super users)</span></label>
            <select name="tenant_id">
                <option value="">— None —</option>
                @foreach ($tenants as $t)
                    <option value="{{ $t->id }}" {{ old('tenant_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
            @error('tenant_id')<div class="error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn-primary" style="width:auto; padding: 10px 22px;">Create user</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tenant</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        <span class="badge {{ $u->role === 'super' ? 'badge-failed' : ($u->role === 'admin' ? 'badge-paid' : 'badge-pending') }}">{{ ucfirst($u->role) }}</span>
                    </td>
                    <td>{{ $u->tenant?->name ?? '—' }}</td>
                    <td>
                        @if ($u->id !== auth()->id())
                            <div style="display:flex; gap: 4px; flex-wrap: wrap;">
                                @can(\App\Support\Permissions::USERS_IMPERSONATE)
                                    @if (! $u->isSuper())
                                        <form method="POST" action="{{ route('super.users.impersonate', $u->id) }}" style="margin: 0;"
                                              data-confirm="You will see the app exactly as {{ $u->name }} does. A red banner will remain at the top so you can exit anytime."
                                              data-confirm-title="Impersonate {{ $u->name }}?"
                                              data-confirm-icon="info"
                                              data-confirm-text="Impersonate">
                                            @csrf
                                            <button type="submit" class="btn-verify" title="Log in as this user">Impersonate</button>
                                        </form>
                                    @endif
                                @endcan
                                <form method="POST" action="{{ route('super.users.delete', $u) }}" style="margin: 0;"
                                      data-confirm="Delete user {{ $u->email }}? This cannot be undone."
                                      data-confirm-title="Delete user?"
                                      data-confirm-icon="error"
                                      data-confirm-text="Delete"
                                      data-confirm-danger="1">
                                    @csrf
                                    <button type="submit" class="btn-verify" style="color: var(--red);">Delete</button>
                                </form>
                            </div>
                        @else
                            <span style="color: var(--text-dim); font-size: 12px;">(you)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $users->links() }}</div>
</div>

<style>
    .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input, .card select { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    .card input:focus, .card select:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 2px rgba(var(--red-rgb), 0.25); }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; }
</style>
@endsection
