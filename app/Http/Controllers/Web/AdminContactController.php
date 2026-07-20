<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\SmsService;
use Illuminate\Http\Request;

class AdminContactController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $groupId = $request->query('group');

        $query = Contact::query()->with('groups')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($groupId) {
            $query->whereHas('groups', fn ($q) => $q->where('contact_groups.id', $groupId));
        }

        $contacts = $query->paginate(50)->withQueryString();
        $groups = ContactGroup::orderBy('name')->get();

        return view('admin.contacts.index', compact('contacts', 'groups', 'search', 'groupId'));
    }

    public function store(Request $request, SmsService $sms)
    {
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
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        );

        if (! empty($data['group_ids'])) {
            $contact->groups()->syncWithoutDetaching($data['group_ids']);
        }

        // Push to TextTango asynchronously so the user isn't blocked by the network call.
        dispatch(function () use ($contact, $sms) {
            $groupProviderIds = $contact->groups()->whereNotNull('provider_id')->pluck('provider_id')->all();
            $resp = $sms->createContact(
                $contact->phone,
                $contact->first_name,
                $contact->last_name,
                $contact->email,
                $groupProviderIds,
            );
            if ($resp['ok']) {
                $providerId = data_get($resp, 'body.data.id');
                if ($providerId) {
                    $contact->update(['provider_id' => $providerId, 'synced_at' => now()]);
                }
            }
            // Refresh group counts
            foreach ($contact->groups as $g) $g->recomputeCount();
        })->afterResponse();

        return redirect()->route('admin.contacts.index')
            ->with('super_flash', ['ok' => true, 'message' => "Contact \"{$contact->displayName()}\" saved."]);
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:contact_groups,id'],
        ]);

        $contact->update([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $contact->groups()->sync($data['group_ids'] ?? []);
        foreach (ContactGroup::all() as $g) $g->recomputeCount();

        return back()->with('super_flash', ['ok' => true, 'message' => 'Contact updated.']);
    }

    public function destroy(Contact $contact, SmsService $sms)
    {
        $providerId = $contact->provider_id;
        $groups = $contact->groups()->pluck('contact_groups.id')->all();
        $contact->delete();

        if ($providerId) {
            dispatch(fn () => $sms->deleteContact($providerId))->afterResponse();
        }

        foreach (ContactGroup::whereIn('id', $groups)->get() as $g) $g->recomputeCount();

        return back()->with('super_flash', ['ok' => true, 'message' => 'Contact deleted.']);
    }

    public function import(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'raw' => ['required', 'string', 'max:200000'],
            'group_id' => ['nullable', 'integer', 'exists:contact_groups,id'],
        ]);

        $lines = preg_split('/\r?\n/', trim($data['raw']));
        $created = 0; $updated = 0; $skipped = 0;
        $affected = collect();

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            // Accept: phone, "phone,first,last,email"
            $cols = array_map('trim', str_getcsv($line));
            $phone = $cols[0] ?? '';
            if ($phone === '') { $skipped++; continue; }

            $contact = Contact::firstOrNew(['phone' => $phone]);
            $isNew = ! $contact->exists;
            $contact->first_name = $cols[1] ?? $contact->first_name;
            $contact->last_name = $cols[2] ?? $contact->last_name;
            $contact->email = $cols[3] ?? $contact->email;
            $contact->save();

            if (! empty($data['group_id'])) {
                $contact->groups()->syncWithoutDetaching([$data['group_id']]);
            }

            $affected->push($contact);
            $isNew ? $created++ : $updated++;
        }

        if (! empty($data['group_id'])) {
            ContactGroup::find($data['group_id'])?->recomputeCount();
        }

        // Push newcomers to TextTango in bulk (best effort, deferred).
        $newRows = $affected->where('provider_id', null)->map(fn ($c) => array_filter([
            'phone' => $c->phone,
            'first_name' => $c->first_name,
            'last_name' => $c->last_name,
            'email' => $c->email,
        ]))->values()->all();

        if (! empty($newRows)) {
            dispatch(function () use ($sms, $newRows) {
                $sms->bulkCreateContacts($newRows);
            })->afterResponse();
        }

        return back()->with('super_flash', [
            'ok' => true,
            'message' => "Imported {$created} new, updated {$updated}, skipped {$skipped}.",
        ]);
    }
}
