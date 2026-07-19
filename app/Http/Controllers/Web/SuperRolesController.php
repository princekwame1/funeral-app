<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;

class SuperRolesController extends Controller
{
    public function index()
    {
        return view('super.roles.index', [
            'modules' => Permissions::MODULES,
            'roleMap' => Permissions::roleMap(),
            'canEdit' => auth()->user()->can(Permissions::ROLES_EDIT),
        ]);
    }

    public function update(Request $request, string $role)
    {
        abort_unless(in_array($role, [User::ROLE_ADMIN, User::ROLE_USER], true), 422, 'Only admin and user roles are editable.');

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        Permissions::saveForRole($role, $data['permissions'] ?? []);

        $count = count($data['permissions'] ?? []);

        return redirect()->route('super.roles.index')
            ->with('super_flash', [
                'ok' => true,
                'message' => "Saved {$count} permission(s) for the " . ucfirst($role) . " role.",
            ]);
    }

    public function reset(string $role)
    {
        abort_unless(in_array($role, [User::ROLE_ADMIN, User::ROLE_USER], true), 422);

        $defaults = Permissions::defaultRoleMap()[$role] ?? [];
        Permissions::saveForRole($role, $defaults);

        return redirect()->route('super.roles.index')
            ->with('super_flash', [
                'ok' => true,
                'message' => 'Reset ' . ucfirst($role) . ' role to defaults.',
            ]);
    }
}
