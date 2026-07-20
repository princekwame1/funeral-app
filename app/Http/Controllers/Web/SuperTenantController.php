<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\SmsCampaign;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperTenantController extends Controller
{
    public function overview()
    {
        $tenants = Tenant::query()
            ->withCount(['users' => fn ($q) => $q->withoutGlobalScopes()])
            ->latest()
            ->get();

        $stats = [
            'tenants' => Tenant::count(),
            'admins' => User::withoutGlobalScopes()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER])->count(),
            'donations' => DB::table('donations')->count(),
            'paid_amount' => (int) DB::table('donations')->where('status', 'paid')->sum('amount'),
            'sms_campaigns' => DB::table('sms_campaigns')->count(),
        ];

        return view('super.overview', compact('tenants', 'stats'));
    }

    public function index()
    {
        $tenants = Tenant::query()
            ->withCount([
                'users' => fn ($q) => $q->withoutGlobalScopes(),
                'donations' => fn ($q) => $q->withoutGlobalScopes(),
                'smsCampaigns' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->latest()
            ->paginate(20);

        return view('super.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('super.tenants.form', ['tenant' => new Tenant()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $tenant = Tenant::create($data);

        return redirect()->route('super.tenants.index')
            ->with('super_flash', ['ok' => true, 'message' => "Tenant \"{$tenant->name}\" created."]);
    }

    public function edit(Tenant $tenant)
    {
        return view('super.tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $this->validated($request, $tenant);
        $tenant->update($data);

        return redirect()->route('super.tenants.index')
            ->with('super_flash', ['ok' => true, 'message' => "Tenant \"{$tenant->name}\" updated."]);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('super.tenants.index')
            ->with('super_flash', ['ok' => true, 'message' => "Tenant \"{$tenant->name}\" deleted."]);
    }

    public function switchTenant(Request $request, Tenant $tenant)
    {
        $request->session()->put('super.active_tenant', $tenant->id);

        return redirect()->route('admin.dashboard')
            ->with('super_flash', ['ok' => true, 'message' => "Now viewing tenant: {$tenant->name}."]);
    }

    public function clearSwitch(Request $request)
    {
        $request->session()->forget('super.active_tenant');

        return redirect()->route('super.overview')
            ->with('super_flash', ['ok' => true, 'message' => 'Cleared tenant filter — viewing all tenants.']);
    }

    public function users()
    {
        $users = User::withoutGlobalScopes()->with('tenant')->latest()->paginate(30);
        $tenants = Tenant::orderBy('name')->get();

        return view('super.users.index', compact('users', 'tenants'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:super,admin,user'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        if ($data['role'] !== User::ROLE_SUPER && empty($data['tenant_id'])) {
            return back()->withInput()->withErrors(['tenant_id' => 'Non-super users must belong to a tenant.']);
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'tenant_id' => $data['role'] === User::ROLE_SUPER ? null : $data['tenant_id'],
        ]);

        return redirect()->route('super.users')
            ->with('super_flash', ['ok' => true, 'message' => 'User created.']);
    }

    public function resetUserPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('super_flash', [
            'ok' => true,
            'message' => "Password reset for {$user->name}.",
        ]);
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('super_flash', ['ok' => false, 'message' => 'You cannot delete yourself.']);
        }

        $user->delete();
        return back()->with('super_flash', ['ok' => true, 'message' => 'User deleted.']);
    }

    private function validated(Request $request, ?Tenant $tenant = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:80', 'unique:tenants,slug,' . ($tenant?->id ?? 'NULL')],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'family_name' => ['nullable', 'string', 'max:200'],
            'deceased_name' => ['nullable', 'string', 'max:200'],
            'deceased_date_of_birth' => ['nullable', 'date'],
            'deceased_date_of_passing' => ['nullable', 'date', 'after_or_equal:deceased_date_of_birth'],
            'brand_primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'splash_image_url' => ['nullable', 'url', 'max:500'],
            'background_image_url' => ['nullable', 'url', 'max:500'],
            'favicon_url' => ['nullable', 'url', 'max:500'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
            'paystack_secret' => ['nullable', 'string', 'max:200'],
            'paystack_public' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
            'plan' => ['nullable', 'in:free,starter,pro'],
            'sms_limit_monthly' => ['nullable', 'integer', 'min:0'],
            'donation_limit_total' => ['nullable', 'integer', 'min:0'],
            'archive_at' => ['nullable', 'date'],
        ]);
    }

    public function unarchive(Tenant $tenant)
    {
        $tenant->update(['archived_at' => null]);
        return redirect()->route('super.tenants.index')
            ->with('super_flash', ['ok' => true, 'message' => "\"{$tenant->name}\" is active again."]);
    }

    public function archiveNow(Tenant $tenant)
    {
        $tenant->update(['archived_at' => now()]);
        return redirect()->route('super.tenants.index')
            ->with('super_flash', ['ok' => true, 'message' => "\"{$tenant->name}\" is now archived (read-only)."]);
    }
}
