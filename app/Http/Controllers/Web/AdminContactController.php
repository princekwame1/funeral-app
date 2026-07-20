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
            'raw' => ['nullable', 'string', 'max:200000'],
            'file' => ['nullable', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt'],
            'group_id' => ['nullable', 'integer', 'exists:contact_groups,id'],
        ]);

        if (! ($data['raw'] ?? null) && ! $request->hasFile('file')) {
            return back()->withInput()->with('super_flash', [
                'ok' => false,
                'message' => 'Paste contacts or upload a CSV/Excel file to import.',
            ]);
        }

        $rows = $request->hasFile('file')
            ? $this->rowsFromSpreadsheet($request->file('file'))
            : $this->rowsFromRaw($data['raw'] ?? '');

        $created = 0; $updated = 0; $skipped = 0;
        $affected = collect();

        foreach ($rows as $cols) {
            // Accept either order: (name, phone) or (phone, name). Detect by which cell looks like a phone.
            $a = trim((string) ($cols[0] ?? ''));
            $b = trim((string) ($cols[1] ?? ''));

            // Skip header rows and blanks
            if ($a === '' || strcasecmp($a, 'phone') === 0 || strcasecmp($a, 'name') === 0) {
                $skipped++;
                continue;
            }

            $looksLikePhone = fn ($v) => (bool) preg_match('/^[+0-9][\d\s\-()]{5,}$/', $v);
            if ($looksLikePhone($a) && ! $looksLikePhone($b)) {
                $phone = $a;
                $name = $b;
            } elseif ($looksLikePhone($b) && ! $looksLikePhone($a)) {
                $phone = $b;
                $name = $a;
            } elseif ($looksLikePhone($a)) {
                // Only phone provided
                $phone = $a;
                $name = $b;
            } else {
                $skipped++;
                continue;
            }

            [$firstName, $lastName] = $this->splitName($name);

            $contact = Contact::firstOrNew(['phone' => $phone]);
            $isNew = ! $contact->exists;
            if ($firstName !== '') $contact->first_name = $firstName;
            if ($lastName !== '') $contact->last_name = $lastName;
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

    /**
     * Split a full name like "Ama Boateng Jr" into ["Ama", "Boateng Jr"].
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') return ['', ''];
        $parts = preg_split('/\s+/', $name, 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * Parse "name,phone" or "phone,name" lines from a textarea.
     */
    private function rowsFromRaw(string $raw): array
    {
        $rows = [];
        foreach (preg_split('/\r?\n/', trim($raw)) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $rows[] = str_getcsv($line);
        }
        return $rows;
    }

    /**
     * Parse the first sheet of a CSV / .xlsx / .xls file into row arrays.
     * Column order expected: phone, first_name, last_name, email (extra cols ignored).
     */
    private function rowsFromSpreadsheet(\Illuminate\Http\UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        // Simple CSV path — cheaper and predictable
        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->rowsFromRaw(file_get_contents($file->getRealPath()) ?: '');
        }

        // .xlsx / .xls via PhpSpreadsheet
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $cells = [];
                foreach ($cellIterator as $cell) {
                    $cells[] = (string) $cell->getValue();
                }
                // Skip fully blank rows
                if (implode('', array_map('trim', $cells)) === '') continue;
                $rows[] = $cells;
            }
            return $rows;
        } catch (\Throwable $e) {
            // Fall back to CSV if PhpSpreadsheet can't read (unusual)
            return $this->rowsFromRaw(file_get_contents($file->getRealPath()) ?: '');
        }
    }
}
