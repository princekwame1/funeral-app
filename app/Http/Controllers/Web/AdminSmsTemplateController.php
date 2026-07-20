<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class AdminSmsTemplateController extends Controller
{
    public function index(Request $request)
    {
        $kind = $request->query('kind', SmsTemplate::KIND_THANKYOU);
        abort_unless(array_key_exists($kind, SmsTemplate::KINDS), 404);

        $templates = SmsTemplate::where('kind', $kind)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('admin.sms.templates.index', [
            'kind' => $kind,
            'templates' => $templates,
            'allKinds' => SmsTemplate::KINDS,
            'tokens' => $this->availableTokens(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        SmsTemplate::create($data);

        return redirect()->route('admin.sms-templates.index', ['kind' => $data['kind']])
            ->with('super_flash', ['ok' => true, 'message' => "Template \"{$data['label']}\" added."]);
    }

    public function update(Request $request, SmsTemplate $template)
    {
        $data = $this->validated($request, $template);

        // Only one is_default per (tenant, kind) — flip the others off if this one is set default.
        if (! empty($data['is_default'])) {
            SmsTemplate::where('kind', $template->kind)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($data);

        return redirect()->route('admin.sms-templates.index', ['kind' => $template->kind])
            ->with('super_flash', ['ok' => true, 'message' => 'Template updated.']);
    }

    public function destroy(SmsTemplate $template)
    {
        $kind = $template->kind;
        $label = $template->label;
        $template->delete();

        return redirect()->route('admin.sms-templates.index', ['kind' => $kind])
            ->with('super_flash', ['ok' => true, 'message' => "Template \"{$label}\" deleted."]);
    }

    private function validated(Request $request, ?SmsTemplate $existing): array
    {
        $data = $request->validate([
            'kind' => ['required', 'in:' . implode(',', array_keys(SmsTemplate::KINDS))],
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/'],
            'label' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1071'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function availableTokens(): array
    {
        return [
            '[DONOR]' => "Donor's first name (thank-you only)",
            '[FULL_NAME]' => "Donor's full name (thank-you only)",
            '[AMOUNT]' => 'Amount + currency (thank-you only)',
            '[REFERENCE]' => 'Payment reference (thank-you only)',
            '[DECEASED]' => "Deceased's name",
            '[FAMILY]' => 'Family name',
            '[DATE]' => 'Event date',
            '[TIME]' => 'Event time',
            '[VENUE]' => 'Venue',
        ];
    }
}
