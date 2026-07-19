@extends('layouts.app')

@section('content')
<h2 style="margin: 0 0 4px;">Funeral programme</h2>
<p style="color: var(--text-muted); margin: 0 0 20px; font-size: 14px;">
    Family &amp; deceased details plus the full schedule of events
    @if ($tenant?->family_name || $tenant?->deceased_name)
        for <strong style="color: var(--text);">the {{ $tenant?->family_name ? $tenant->family_name . ' Family' : '' }}{{ $tenant?->deceased_name && $tenant?->family_name ? ' · ' : '' }}{{ $tenant?->deceased_name ?? '' }}</strong>.
    @else
        for this tenant.
    @endif
</p>

@can(\App\Support\Permissions::EVENTS_MANAGE)
<div class="card" data-tour="funeral-info" style="margin-bottom: 16px;">
    <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 12px; flex-wrap: wrap;">
        <div>
            <h3 style="margin: 0 0 2px; font-size: 15px;">Family &amp; deceased</h3>
            <p style="color: var(--text-muted); margin: 0; font-size: 13px;">Used across the app, in the header of this page and in exported reports.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.events.funeral-info') }}">
        @csrf
        <div class="event-grid">
            <div class="form-group">
                <label class="field-label" for="family_name">Family name</label>
                <input type="text" name="family_name" id="family_name" value="{{ old('family_name', $tenant?->family_name) }}" maxlength="200" placeholder="e.g. Boateng">
                @error('family_name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="deceased_name">Deceased name</label>
                <input type="text" name="deceased_name" id="deceased_name" value="{{ old('deceased_name', $tenant?->deceased_name) }}" maxlength="200" placeholder="e.g. Kwame Boateng Snr.">
                @error('deceased_name')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="deceased_date_of_birth">Date of birth</label>
                <input type="date" name="deceased_date_of_birth" id="deceased_date_of_birth" value="{{ old('deceased_date_of_birth', $tenant?->deceased_date_of_birth?->format('Y-m-d')) }}">
                @error('deceased_date_of_birth')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="deceased_date_of_passing">Date of passing</label>
                <input type="date" name="deceased_date_of_passing" id="deceased_date_of_passing" value="{{ old('deceased_date_of_passing', $tenant?->deceased_date_of_passing?->format('Y-m-d')) }}">
                @error('deceased_date_of_passing')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">Save family &amp; deceased</button>
    </form>
</div>
@else
    @if ($tenant?->family_name || $tenant?->deceased_name)
        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin: 0 0 8px; font-size: 15px;">In loving memory</h3>
            <div style="color: var(--text); font-size: 14px;">
                @if ($tenant->deceased_name)<strong>{{ $tenant->deceased_name }}</strong>@endif
                @if ($tenant->family_name)<span style="color: var(--text-muted);"> · {{ $tenant->family_name }} Family</span>@endif
            </div>
            @if ($tenant->deceased_date_of_birth || $tenant->deceased_date_of_passing)
                <div style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
                    {{ $tenant->deceased_date_of_birth?->format('d M Y') ?? '?' }}
                    &nbsp;—&nbsp;
                    {{ $tenant->deceased_date_of_passing?->format('d M Y') ?? '?' }}
                </div>
            @endif
        </div>
    @endif
@endcan

@can(\App\Support\Permissions::EVENTS_MANAGE)
<div class="card" data-tour="add-event" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 12px; font-size: 15px;">Add an event</h3>
    <form method="POST" action="{{ route('admin.events.store') }}">
        @csrf
        <div class="event-grid">
            <div class="form-group">
                <label class="field-label" for="title">Event title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="200" placeholder="e.g. Wake-keeping">
                @error('title')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="starts_at">Starts</label>
                <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" required>
                @error('starts_at')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="ends_at">Ends <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <input type="datetime-local" name="ends_at" id="ends_at" value="{{ old('ends_at') }}">
                @error('ends_at')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="field-label" for="venue">Venue</label>
                <input type="text" name="venue" id="venue" value="{{ old('venue') }}" maxlength="300" placeholder="e.g. Family House, East Legon">
                @error('venue')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group event-grid-wide">
                <label class="field-label" for="location_url">Map / directions link <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <input type="url" name="location_url" id="location_url" value="{{ old('location_url') }}" maxlength="500" placeholder="https://maps.google.com/…">
                @error('location_url')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group event-grid-wide">
                <label class="field-label" for="description">Notes <span style="color: var(--text-dim); font-weight: 400;">(optional)</span></label>
                <textarea name="description" id="description" rows="3" placeholder="Dress code, contact person, order of programme…">{{ old('description') }}</textarea>
                @error('description')<div class="error">{{ $message }}</div>@enderror
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 22px;">+ Add event</button>
    </form>
</div>
@endcan

<h3 data-tour="upcoming-events" style="margin: 24px 0 12px; font-size: 15px; color: var(--text-muted); font-weight: 500;">Upcoming ({{ $upcoming->count() }})</h3>
@if ($upcoming->isEmpty())
    <div class="card empty" data-tour="upcoming-list" style="text-align: center; padding: 30px; color: var(--text-dim);">No upcoming events. Add one above.</div>
@else
    <div class="events-list" data-tour="upcoming-list">
        @foreach ($upcoming as $event)
            @include('admin.events.partials.card', ['event' => $event, 'past' => false])
        @endforeach
    </div>
@endif

@if ($past->count())
    <h3 style="margin: 28px 0 12px; font-size: 15px; color: var(--text-muted); font-weight: 500;">Past ({{ $past->count() }})</h3>
    <div class="events-list past">
        @foreach ($past as $event)
            @include('admin.events.partials.card', ['event' => $event, 'past' => true])
        @endforeach
    </div>
@endif

<style>
    .event-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 14px; }
    .event-grid-wide { grid-column: 1 / -1; }
    .field-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
    .card input, .card select, .card textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--border); background: var(--surface-2); color: var(--text); border-radius: 6px; font-size: 14px; font-family: inherit; }
    .card textarea { resize: vertical; }
    .card input:focus, .card select:focus, .card textarea:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 3px rgba(var(--red-rgb), 0.25); }

    .events-list { display: flex; flex-direction: column; gap: 12px; }
    .event-card { display: grid; grid-template-columns: 90px 1fr auto; gap: 16px; padding: 16px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; align-items: start; }
    .events-list.past .event-card { opacity: 0.6; }
    .event-date { text-align: center; padding: 10px 8px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; }
    .event-date .day { font-size: 22px; font-weight: 700; color: var(--red); line-height: 1; }
    .event-date .month { font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted); margin-top: 4px; }
    .event-date .year { font-size: 10px; color: var(--text-dim); margin-top: 2px; }
    .event-body { min-width: 0; }
    .event-title { font-size: 15px; font-weight: 600; color: var(--text); margin: 0 0 6px; }
    .event-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
    .event-meta span { display: inline-flex; align-items: center; gap: 4px; }
    .event-meta a { color: var(--red); text-decoration: none; }
    .event-meta a:hover { text-decoration: underline; }
    .event-desc { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
    .event-actions { display: flex; flex-direction: column; gap: 4px; }
    .btn-verify { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 5px 12px; border-radius: 999px; font-size: 12px; cursor: pointer; font-family: inherit; }
    .btn-verify:hover { border-color: var(--red); background: rgba(var(--red-rgb), 0.08); }
    .event-edit-form { display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); grid-column: 1 / -1; }
    .event-edit-form.open { display: block; }
</style>

<script>
    document.querySelectorAll('[data-edit-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.editToggle;
            var form = document.getElementById('edit-event-' + id);
            if (form) form.classList.toggle('open');
        });
    });
</script>

<script>
    window.__tourKey = 'funeral-v1';
    window.__tourSteps = [
        @can(\App\Support\Permissions::EVENTS_MANAGE)
        {
            target: '[data-tour="funeral-info"]',
            position: 'bottom',
            title: 'Family & deceased',
            body: 'Fill in the family name, the deceased\'s name, and their dates. This information shows up in the sidebar heading, exported reports, and SMS placeholders like <strong>[NAME]</strong>.',
        },
        {
            target: '[data-tour="add-event"]',
            position: 'bottom',
            title: 'Add an event',
            body: 'Wake-keeping, burial, thanksgiving — anything on the programme. Include a venue and (optionally) a Google Maps link so guests can navigate. Times use your local zone.',
        },
        @endcan
        {
            target: '[data-tour="upcoming-events"]',
            position: 'bottom',
            title: 'Upcoming schedule',
            body: 'Everything happening from today onwards, sorted by start time. The next four events also appear on the Dashboard so nothing sneaks up on you.',
        },
        {
            target: '[data-tour="upcoming-list"]',
            position: 'top',
            title: 'Event cards',
            body: 'Each event card shows the date, time, venue, directions link, and any notes. @can(\App\Support\Permissions::EVENTS_MANAGE)Use the <strong>Edit</strong> and <strong>Remove</strong> buttons on each card to make changes.@endcan',
        },
    ];
</script>
@endsection
