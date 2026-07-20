<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\SmsService;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize($request, Permissions::CONTACTS_VIEW);
        $q = Contact::query()->with('groups');

        if ($s = $request->query('q')) {
            $q->where(function ($x) use ($s) {
                $x->where('phone', 'like', "%{$s}%")
                    ->orWhere('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }
        if ($gid = $request->query('group_id')) {
            $q->whereHas('groups', fn ($qq) => $qq->where('contact_groups.id', $gid));
        }

        return response()->json($q->latest()->paginate(50));
    }

    public function store(Request $request, SmsService $sms): JsonResponse
    {
        $this->authorize($request, Permissions::CONTACTS_MANAGE);
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:contact_groups,id'],
        ]);

        $contact = Contact::updateOrCreate(
            ['phone' => $data['phone']],
            collect($data)->only(['first_name', 'last_name', 'email', 'notes'])->all(),
        );
        if (! empty($data['group_ids'])) {
            $contact->groups()->syncWithoutDetaching($data['group_ids']);
        }

        dispatch(function () use ($contact, $sms) {
            $ids = $contact->groups()->whereNotNull('provider_id')->pluck('provider_id')->all();
            $resp = $sms->createContact($contact->phone, $contact->first_name, $contact->last_name, $contact->email, $ids);
            if ($resp['ok'] && $pid = data_get($resp, 'body.data.id')) {
                $contact->update(['provider_id' => $pid, 'synced_at' => now()]);
            }
            foreach ($contact->groups as $g) $g->recomputeCount();
        })->afterResponse();

        return response()->json(['contact' => $contact->fresh()->load('groups')], 201);
    }

    public function destroy(Request $request, Contact $contact, SmsService $sms): JsonResponse
    {
        $this->authorize($request, Permissions::CONTACTS_MANAGE);
        $providerId = $contact->provider_id;
        $groupIds = $contact->groups()->pluck('contact_groups.id')->all();
        $contact->delete();

        if ($providerId) {
            dispatch(fn () => $sms->deleteContact($providerId))->afterResponse();
        }
        foreach (ContactGroup::whereIn('id', $groupIds)->get() as $g) $g->recomputeCount();

        return response()->json(['ok' => true]);
    }

    // --- Contact groups ---

    public function groupsIndex(Request $request): JsonResponse
    {
        $this->authorize($request, Permissions::CONTACTS_VIEW);
        return response()->json(ContactGroup::withCount('contacts')->orderBy('name')->get());
    }

    public function groupsStore(Request $request, SmsService $sms): JsonResponse
    {
        $this->authorize($request, Permissions::CONTACTS_MANAGE);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        $group = ContactGroup::create($data);
        dispatch(function () use ($group, $sms) {
            $resp = $sms->createContactGroup($group->name, $group->description);
            if ($resp['ok'] && $pid = data_get($resp, 'body.data.id')) {
                $group->update(['provider_id' => $pid, 'synced_at' => now()]);
            }
        })->afterResponse();

        return response()->json(['group' => $group], 201);
    }

    public function groupsDestroy(Request $request, ContactGroup $group, SmsService $sms): JsonResponse
    {
        $this->authorize($request, Permissions::CONTACTS_MANAGE);
        $providerId = $group->provider_id;
        $group->delete();
        if ($providerId) {
            dispatch(fn () => $sms->deleteContactGroup($providerId))->afterResponse();
        }
        return response()->json(['ok' => true]);
    }

    private function authorize(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, 'Missing permission: ' . $permission);
    }
}
