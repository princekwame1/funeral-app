<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantEvent;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can(Permissions::EVENTS_VIEW), 403);

        $now = Carbon::now();
        return response()->json([
            'upcoming' => TenantEvent::query()->where('starts_at', '>=', $now)->orderBy('starts_at')->get(),
            'past' => TenantEvent::query()->where('starts_at', '<', $now)->orderBy('starts_at', 'desc')->limit(30)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can(Permissions::EVENTS_MANAGE), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'venue' => ['nullable', 'string', 'max:300'],
            'location_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        return response()->json(['event' => TenantEvent::create($data)], 201);
    }

    public function destroy(Request $request, TenantEvent $event): JsonResponse
    {
        abort_unless($request->user()->can(Permissions::EVENTS_MANAGE), 403);
        $event->delete();
        return response()->json(['ok' => true]);
    }
}
