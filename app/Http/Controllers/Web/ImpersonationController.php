<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, int $userId)
    {
        $me = $request->user();
        abort_unless($me && $me->isSuper(), 403, 'Only super admins can impersonate.');

        $target = User::withoutGlobalScopes()->find($userId);
        abort_unless($target, 404, 'User not found.');

        if ($target->id === $me->id) {
            return back()->with('super_flash', [
                'ok' => false,
                'message' => 'You cannot impersonate yourself.',
            ]);
        }

        if ($target->isSuper()) {
            return back()->with('super_flash', [
                'ok' => false,
                'message' => 'You cannot impersonate another super admin.',
            ]);
        }

        $request->session()->put('impersonator_id', $me->id);
        // Clear any tenant-switch context so tenant is derived from the impersonated user
        $request->session()->forget('super.active_tenant');

        // Use the model directly — Auth::loginUsingId would re-query with tenant scope,
        // which could silently fail if the current tenant context is a mismatch.
        Auth::login($target);
        $request->session()->regenerate();
        // Re-persist impersonator_id after regenerate (regenerate migrates data by default, but be safe)
        $request->session()->put('impersonator_id', $me->id);

        return redirect()->route('admin.dashboard')
            ->with('impersonation_flash', "You are now viewing the app as {$target->name}.");
    }

    public function stop(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        if (! $impersonatorId) {
            return redirect()->route('admin.dashboard');
        }

        $original = User::withoutGlobalScopes()->find($impersonatorId);
        if (! $original) {
            Auth::logout();
            return redirect()->route('login');
        }

        // The tenant scope is bound to the impersonated user's tenant right now, so
        // Auth::loginUsingId($original->id) would silently return no user because the
        // super's tenant_id is null. Use Auth::login() with the already-fetched model.
        Auth::login($original);
        $request->session()->regenerate();

        return redirect()->route('super.users')
            ->with('super_flash', [
                'ok' => true,
                'message' => "Returned to your account: {$original->name}.",
            ]);
    }
}
