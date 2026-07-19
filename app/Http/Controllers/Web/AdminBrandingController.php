<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminBrandingController extends Controller
{
    private const IMAGE_FIELDS = [
        'logo' => 'logo_url',
        'splash' => 'splash_image_url',
        'background' => 'background_image_url',
        'favicon' => 'favicon_url',
    ];

    public function edit(CurrentTenant $current)
    {
        $tenant = $current->get();

        if (! $tenant) {
            return redirect()->route('super.tenants.index')
                ->with('super_flash', ['ok' => false, 'message' => 'Pick a tenant (click Enter) to edit its branding.']);
        }

        return view('admin.branding.edit', compact('tenant'));
    }

    public function update(Request $request, CurrentTenant $current)
    {
        $tenant = $current->get();

        if (! $tenant) {
            return redirect()->route('super.tenants.index')
                ->with('super_flash', ['ok' => false, 'message' => 'No active tenant.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'brand_primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'splash_image_url' => ['nullable', 'url', 'max:500'],
            'background_image_url' => ['nullable', 'url', 'max:500'],
            'favicon_url' => ['nullable', 'url', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'splash_file' => ['nullable', 'image', 'max:5120'],
            'background_file' => ['nullable', 'image', 'max:5120'],
            'favicon_file' => ['nullable', 'image', 'max:512'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
        ]);

        foreach (self::IMAGE_FIELDS as $key => $column) {
            $fileField = $key . '_file';
            $clearField = 'clear_' . $key;

            if ($request->boolean($clearField)) {
                $this->deleteExisting($tenant->$column);
                $data[$column] = null;
                continue;
            }

            if ($request->hasFile($fileField)) {
                $this->deleteExisting($tenant->$column);
                $path = $request->file($fileField)->store("tenants/{$tenant->id}", 'public');
                // Store a root-relative URL so images survive APP_URL / scheme / port changes.
                $data[$column] = '/storage/' . ltrim($path, '/');
            }
            unset($data[$fileField]);
        }

        $tenant->update($data);

        return redirect()
            ->route('super.branding.edit')
            ->with('branding_flash', ['ok' => true, 'message' => 'Branding updated.']);
    }

    private function deleteExisting(?string $url): void
    {
        if (! $url) return;
        // Support both root-relative (/storage/...) and legacy absolute (http.../storage/...) URLs.
        $path = null;
        if (str_starts_with($url, '/storage/')) {
            $path = substr($url, strlen('/storage/'));
        } else {
            $marker = '/storage/';
            $pos = strpos($url, $marker);
            if ($pos !== false) {
                $path = substr($url, $pos + strlen($marker));
            }
        }
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
