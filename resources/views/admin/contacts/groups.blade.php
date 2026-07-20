@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0 0 4px;">Contact Groups</h2>
        <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Organise contacts into groups. Bulk SMS can target a group directly.</p>
    </div>
    <a href="{{ route('admin.contacts.index') }}" class="btn-outline">Back to contacts</a>
</div>

@can(\App\Support\Permissions::CONTACTS_MANAGE)
<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 12px; font-size: 15px;">Add group</h3>
    <form method="POST" action="{{ route('admin.contact-groups.store') }}">
        @csrf
        <div class="group-form-grid">
            <div class="form-group">
                <label class="field-label">Name</label>
                <input type="text" name="name" required maxlength="150" placeholder="e.g. Family, Church, Colleagues">
                @error('name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group group-form-wide">
                <label class="field-label">Description <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <input type="text" name="description" maxlength="300">
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">Create group</button>
    </form>
</div>
@endcan

<div class="group-cards">
    @foreach ($groups as $group)
        <div class="group-card">
            <div class="group-head">
                <div>
                    <div class="group-name">{{ $group->name }}</div>
                    <div class="group-count">{{ number_format($group->contact_count) }} contact(s)</div>
                </div>
                @if ($group->provider_id)
                    <span class="badge badge-paid" title="Synced to TextTango">Synced</span>
                @else
                    <span class="badge badge-pending" title="Sync pending">Pending</span>
                @endif
            </div>
            @if ($group->description)
                <div class="group-desc">{{ $group->description }}</div>
            @endif
            <div class="group-actions">
                <a href="{{ route('admin.contacts.index', ['group' => $group->id]) }}" class="btn-verify">View contacts</a>
                @can(\App\Support\Permissions::CONTACTS_MANAGE)
                    <button type="button" class="btn-verify" data-edit-group="{{ $group->id }}">Edit</button>
                    <form method="POST" action="{{ route('admin.contact-groups.destroy', $group) }}" style="margin: 0;"
                          data-confirm="Delete the {{ $group->name }} group? Contacts stay; only the grouping is removed."
                          data-confirm-title="Delete group?"
                          data-confirm-icon="error"
                          data-confirm-text="Delete"
                          data-confirm-danger="1">
                        @csrf
                        <button type="submit" class="btn-verify" style="color: var(--red);">Delete</button>
                    </form>
                @endcan
            </div>
            @can(\App\Support\Permissions::CONTACTS_MANAGE)
            <form method="POST" action="{{ route('admin.contact-groups.update', $group) }}" class="group-edit-form" id="edit-group-{{ $group->id }}">
                @csrf
                <div class="form-group">
                    <label class="field-label">Name</label>
                    <input type="text" name="name" value="{{ $group->name }}" required maxlength="150">
                </div>
                <div class="form-group">
                    <label class="field-label">Description</label>
                    <input type="text" name="description" value="{{ $group->description }}" maxlength="300">
                </div>
                <button type="submit" class="btn-primary" style="width: auto; padding: 8px 18px;">Save</button>
            </form>
            @endcan
        </div>
    @endforeach
    @if ($groups->isEmpty())
        <div class="empty" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-dim);">
            No groups yet. Create one above to organise your contacts.
        </div>
    @endif
</div>

<style>
    .group-form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 12px; }
    .group-form-wide { grid-column: auto; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; }
    .card input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.2); }
    .group-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
    .group-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px; display: flex; flex-direction: column; gap: 10px; }
    .group-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .group-name { font-size: 16px; font-weight: 600; color: var(--text); }
    .group-count { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .group-desc { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
    .group-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; text-decoration: none; font-family: inherit; }
    .btn-verify:hover { color: var(--text); border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }
    .btn-outline { padding: 10px 16px; background: transparent; color: var(--text-muted); border: 1px solid var(--border); border-radius: 6px; text-decoration: none; font-size: 14px; }
    .btn-outline:hover { color: var(--text); border-color: var(--red); }
    .group-edit-form { display: none; padding-top: 10px; border-top: 1px solid var(--border); margin-top: 4px; }
    .group-edit-form.open { display: block; }
</style>

<script>
    document.querySelectorAll('[data-edit-group]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.editGroup;
            document.getElementById('edit-group-' + id).classList.toggle('open');
        });
    });
</script>
@endsection
