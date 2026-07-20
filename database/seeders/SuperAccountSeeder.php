<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAccountSeeder extends Seeder
{
    /**
     * Seeds the primary super admin. Idempotent — safe to re-run.
     *
     * Override via env for anything other than local dev:
     *   SUPER_EMAIL=you@funeraldonations.com
     *   SUPER_NAME="Platform Owner"
     *   SUPER_PASSWORD=strongsecret
     */
    public function run(): void
    {
        $email = (string) env('SUPER_EMAIL', 'super@funeral.test');
        $name = (string) env('SUPER_NAME', 'Platform Super');
        $password = (string) env('SUPER_PASSWORD', 'password');

        // The updateOrCreate must bypass the User tenant scope in case a request
        // context accidentally has a CurrentTenant set. Since seeders run from
        // the CLI, CurrentTenant is unset — so this works either way.
        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => User::ROLE_SUPER,
                'tenant_id' => null, // super is not bound to any tenant
            ]
        );

        $this->command?->info("✓ Super admin ready → {$user->email} (password: {$password})");
    }
}
