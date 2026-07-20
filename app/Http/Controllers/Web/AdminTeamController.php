<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminTeamController extends Controller
{
    public function index(CurrentTenant $current)
    {
        // TenantScope on User keeps this filtered to the admin's own tenant.
        $users = User::query()
            ->latest()
            ->paginate(30);

        return view('admin.team.index', [
            'users' => $users,
            'tenant' => $current->get(),
        ]);
    }

    public function store(Request $request, CurrentTenant $current)
    {
        $tenant = $current->get();
        abort_unless($tenant, 400, 'No tenant context — cannot invite users.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            // Tenant admins can only create admin or user roles within their tenant.
            'role' => ['required', 'in:admin,user'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'tenant_id' => $tenant->id,
        ]);

        return redirect()->route('admin.team.index')
            ->with('team_flash', ['ok' => true, 'message' => "{$data['name']} added to your team as {$data['role']}."]);
    }

    public function resetPassword(Request $request, int $userId)
    {
        $user = User::query()->find($userId); // TenantScope keeps this within tenant
        abort_unless($user, 404, 'User not found in your tenant.');

        if ($user->isSuper()) {
            return back()->with('team_flash', ['ok' => false, 'message' => 'Only another super admin can reset a super\'s password.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('team_flash', [
            'ok' => true,
            'message' => "Password reset for {$user->name}. Share the new password with them privately.",
        ]);
    }

    public function destroy(Request $request, int $userId, CurrentTenant $current)
    {
        $tenant = $current->get();
        abort_unless($tenant, 400, 'No tenant context.');

        $user = User::query()->find($userId); // TenantScope prevents cross-tenant removal.
        abort_unless($user, 404, 'User not found in your tenant.');

        if ($user->id === $request->user()->id) {
            return back()->with('team_flash', ['ok' => false, 'message' => 'You cannot remove yourself.']);
        }

        if ($user->isSuper()) {
            return back()->with('team_flash', ['ok' => false, 'message' => 'Super admins can only be removed by another super admin.']);
        }

        $user->delete();

        return back()->with('team_flash', ['ok' => true, 'message' => "{$user->name} removed from your team."]);
    }
}
