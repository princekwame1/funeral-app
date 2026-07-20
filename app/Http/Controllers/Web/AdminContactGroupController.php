<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactGroup;
use App\Services\SmsService;
use Illuminate\Http\Request;

class AdminContactGroupController extends Controller
{
    public function index()
    {
        $groups = ContactGroup::query()
            ->withCount('contacts')
            ->orderBy('name')
            ->get();

        return view('admin.contacts.groups', compact('groups'));
    }

    public function store(Request $request, SmsService $sms)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        $group = ContactGroup::create($data);

        // Push to TextTango after response
        dispatch(function () use ($group, $sms) {
            $resp = $sms->createContactGroup($group->name, $group->description);
            if ($resp['ok']) {
                $providerId = data_get($resp, 'body.data.id');
                if ($providerId) {
                    $group->update(['provider_id' => $providerId, 'synced_at' => now()]);
                }
            }
        })->afterResponse();

        return redirect()->route('admin.contact-groups.index')
            ->with('super_flash', ['ok' => true, 'message' => "Group \"{$group->name}\" created."]);
    }

    public function update(Request $request, ContactGroup $group, SmsService $sms)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        $group->update($data);

        if ($group->provider_id) {
            dispatch(fn () => $sms->updateContactGroup($group->provider_id, $data))->afterResponse();
        }

        return back()->with('super_flash', ['ok' => true, 'message' => 'Group updated.']);
    }

    public function destroy(ContactGroup $group, SmsService $sms)
    {
        $providerId = $group->provider_id;
        $name = $group->name;
        $group->delete();

        if ($providerId) {
            dispatch(fn () => $sms->deleteContactGroup($providerId))->afterResponse();
        }

        return back()->with('super_flash', ['ok' => true, 'message' => "Group \"{$name}\" deleted."]);
    }
}
