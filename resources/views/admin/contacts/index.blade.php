@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0 0 4px;">Contacts</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Saved recipients for bulk SMS. New contacts are pushed to TextTango automatically.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('admin.contact-groups.index') }}" class="btn-outline">Manage groups</a>
        @can(\App\Support\Permissions::CONTACTS_MANAGE)
            <button type="button" class="btn-primary" onclick="document.getElementById('addContactCard').classList.toggle('open')" style="width: auto; padding: 10px 20px;">+ Add contact</button>
        @endcan
    </div>
</div>

@can(\App\Support\Permissions::CONTACTS_MANAGE)
<div id="addContactCard" class="card collapsible" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 12px; font-size: 15px;">Add / update contact</h3>
    <form method="POST" action="{{ route('admin.contacts.store') }}">
        @csrf
        <div class="contact-grid">
            <div class="form-group">
                <label class="field-label">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required maxlength="30" placeholder="0244123456">
                @error('phone')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" maxlength="100">
            </div>
            <div class="form-group">
                <label class="field-label">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" maxlength="100">
            </div>
            <div class="form-group">
                <label class="field-label">Email <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <input type="email" name="email" value="{{ old('email') }}" maxlength="150">
            </div>
        </div>
        <div class="form-group" style="margin-top: 8px;">
            <label class="field-label">Add to groups</label>
            <div class="group-pills">
                @foreach ($groups as $g)
                    <label class="group-pill"><input type="checkbox" name="group_ids[]" value="{{ $g->id }}"> {{ $g->name }}</label>
                @endforeach
                @if ($groups->isEmpty())<span style="color: var(--text-dim); font-size: 13px;">No groups yet — <a href="{{ route('admin.contact-groups.index') }}" style="color: var(--red);">create one</a>.</span>@endif
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">Save contact</button>
    </form>
</div>
@endcan

@can(\App\Support\Permissions::CONTACTS_IMPORT)
<div id="importCard" class="card collapsible" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 6px; font-size: 15px;">Import from CSV / paste</h3>
    <p style="color: var(--text-muted); margin: 0 0 12px; font-size: 13px;">One contact per line. Format: <code style="background: var(--surface-2); padding: 1px 6px; border-radius: 4px;">phone,first,last,email</code>. Only <strong>phone</strong> is required.</p>
    <form method="POST" action="{{ route('admin.contacts.import') }}">
        @csrf
        <div class="form-group">
            <textarea name="raw" rows="6" placeholder="0244123456,Ama,Boateng,ama@example.com&#10;0201234567,John,Mensah&#10;0555555555" required></textarea>
        </div>
        <div class="form-group">
            <label class="field-label">Assign all imported contacts to a group <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
            <select name="group_id">
                <option value="">— None —</option>
                @foreach ($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">Import</button>
    </form>
</div>
@endcan

<div class="card">
    <form method="GET" action="{{ route('admin.contacts.index') }}" class="filters" style="margin-bottom: 16px;">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search phone, name, email...">
        <select name="group">
            <option value="">All groups</option>
            @foreach ($groups as $g)
                <option value="{{ $g->id }}" {{ (int) $groupId === $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->contact_count }})</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>

    @if ($contacts->count() === 0)
        <div class="empty" style="text-align: center; padding: 30px; color: var(--text-dim);">No contacts yet. Add one above or import.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Groups</th>
                    <th>Synced</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contacts as $c)
                    <tr>
                        <td style="font-weight: 500;">{{ $c->displayName() }}</td>
                        <td style="font-family: monospace;">{{ $c->phone }}</td>
                        <td>{{ $c->email ?? '—' }}</td>
                        <td>
                            @foreach ($c->groups as $g)
                                <span class="badge badge-method-offline">{{ $g->name }}</span>
                            @endforeach
                            @if ($c->groups->isEmpty())<span style="color: var(--text-dim);">—</span>@endif
                        </td>
                        <td>
                            @if ($c->provider_id)
                                <span class="badge badge-paid" title="Synced to TextTango">✓</span>
                            @else
                                <span class="badge badge-pending" title="Pending sync">…</span>
                            @endif
                        </td>
                        <td>
                            @can(\App\Support\Permissions::CONTACTS_MANAGE)
                                <form method="POST" action="{{ route('admin.contacts.destroy', $c) }}" style="margin: 0;"
                                      data-confirm="Delete {{ $c->displayName() }} ({{ $c->phone }})?"
                                      data-confirm-title="Delete contact?"
                                      data-confirm-icon="error"
                                      data-confirm-text="Delete"
                                      data-confirm-danger="1">
                                    @csrf
                                    <button type="submit" class="btn-verify" style="color: var(--red);">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $contacts->links() }}</div>
    @endif
</div>

<style>
    .collapsible { display: none; }
    .collapsible.open { display: block; }
    .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input[type=text], .card input[type=email], .card select, .card textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    .card textarea { resize: vertical; font-family: 'SF Mono', monospace; font-size: 13px; }
    .card input:focus, .card select:focus, .card textarea:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }
    .group-pills { display: flex; gap: 6px; flex-wrap: wrap; }
    .group-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 999px; font-size: 12px; cursor: pointer; color: var(--text-muted); }
    .group-pill:has(input:checked) { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.12); }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; }
    .btn-verify:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }
    .btn-outline { padding: 10px 16px; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; }
    .btn-outline:hover { color: var(--text); border-color: var(--red); }
</style>
@endsection
