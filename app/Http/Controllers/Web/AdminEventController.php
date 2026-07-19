<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TenantEvent;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminEventController extends Controller
{
    public function index(CurrentTenant $current)
    {
        $now = Carbon::now();

        $upcoming = TenantEvent::query()
            ->where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->get();

        $past = TenantEvent::query()
            ->where('starts_at', '<', $now)
            ->orderBy('starts_at', 'desc')
            ->get();

        return view('admin.events.index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'tenant' => $current->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        TenantEvent::create($data);

        return redirect()->route('admin.events.index')
            ->with('super_flash', ['ok' => true, 'message' => "Event \"{$data['title']}\" added."]);
    }

    public function update(Request $request, TenantEvent $event)
    {
        $data = $this->validated($request);
        $event->update($data);

        return redirect()->route('admin.events.index')
            ->with('super_flash', ['ok' => true, 'message' => 'Event updated.']);
    }

    public function destroy(TenantEvent $event)
    {
        $title = $event->title;
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('super_flash', ['ok' => true, 'message' => "Event \"{$title}\" removed."]);
    }

    public function updateFuneralInfo(Request $request, CurrentTenant $current)
    {
        $tenant = $current->get();
        abort_unless($tenant, 400, 'No tenant context.');

        $data = $request->validate([
            'family_name' => ['nullable', 'string', 'max:200'],
            'deceased_name' => ['nullable', 'string', 'max:200'],
            'deceased_date_of_birth' => ['nullable', 'date'],
            'deceased_date_of_passing' => ['nullable', 'date', 'after_or_equal:deceased_date_of_birth'],
        ]);

        $tenant->update($data);

        return redirect()->route('admin.events.index')
            ->with('super_flash', ['ok' => true, 'message' => 'Family & deceased details updated.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'venue' => ['nullable', 'string', 'max:300'],
            'location_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
