<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteToSuper extends Command
{
    protected $signature = 'user:promote-super {email}';

    protected $description = 'Promote a user (by email) to super admin. Clears their tenant_id so they see everything.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user with email {$email}");
            return self::FAILURE;
        }

        $user->update([
            'role' => User::ROLE_SUPER,
            'tenant_id' => null,
        ]);

        $this->info("{$user->name} ({$user->email}) is now a Super Admin.");
        return self::SUCCESS;
    }
}
