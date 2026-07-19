<div class="event-card">
    <div class="event-date">
        <div class="day">{{ $event->starts_at->format('d') }}</div>
        <div class="month">{{ $event->starts_at->format('M') }}</div>
        <div class="year">{{ $event->starts_at->format('Y') }}</div>
    </div>
    <div class="event-body">
        <div class="event-title">{{ $event->title }}</div>
        <div class="event-meta">
            <span>&#128337; {{ $event->starts_at->format('D, d M Y · H:i') }}@if ($event->ends_at) — {{ $event->ends_at->format('H:i') }}@endif</span>
            @if ($event->venue)<span>&#128205; {{ $event->venue }}</span>@endif
            @if ($event->location_url)<span><a href="{{ $event->location_url }}" target="_blank" rel="noopener">Directions →</a></span>@endif
        </div>
        @if ($event->description)
            <div class="event-desc">{{ $event->description }}</div>
        @endif
    </div>
    <div class="event-actions">
        @can(\App\Support\Permissions::EVENTS_MANAGE)
            <button type="button" class="btn-verify" data-edit-toggle="{{ $event->id }}">Edit</button>
            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" style="margin: 0;"
                  data-confirm="Remove {{ $event->title }} from the schedule?"
                  data-confirm-title="Remove event?"
                  data-confirm-icon="warning"
                  data-confirm-text="Remove"
                  data-confirm-danger="1">
                @csrf
                <button type="submit" class="btn-verify" style="color: var(--red);">Remove</button>
            </form>
        @endcan
    </div>

    @can(\App\Support\Permissions::EVENTS_MANAGE)
    <form method="POST" action="{{ route('admin.events.update', $event) }}" class="event-edit-form" id="edit-event-{{ $event->id }}">
        @csrf
        <div class="event-grid">
            <div class="form-group">
                <label class="field-label">Title</label>
                <input type="text" name="title" value="{{ $event->title }}" required maxlength="200">
            </div>
            <div class="form-group">
                <label class="field-label">Starts</label>
                <input type="datetime-local" name="starts_at" value="{{ $event->starts_at->format('Y-m-d\TH:i') }}" required>
            </div>
            <div class="form-group">
                <label class="field-label">Ends</label>
                <input type="datetime-local" name="ends_at" value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}">
            </div>
            <div class="form-group">
                <label class="field-label">Venue</label>
                <input type="text" name="venue" value="{{ $event->venue }}" maxlength="300">
            </div>
            <div class="form-group event-grid-wide">
                <label class="field-label">Directions link</label>
                <input type="url" name="location_url" value="{{ $event->location_url }}" maxlength="500">
            </div>
            <div class="form-group event-grid-wide">
                <label class="field-label">Notes</label>
                <textarea name="description" rows="3">{{ $event->description }}</textarea>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="width: auto; padding: 8px 20px;">Save changes</button>
    </form>
    @endcan
</div>
