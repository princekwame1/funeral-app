<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SuperAccountSeeder::class);

        $tenant = Tenant::query()->orderBy('id')->first();

        User::updateOrCreate(
            ['email' => 'admin@funeral.test'],
            [
                'name' => 'Funeral Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'tenant_id' => $tenant?->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@funeral.test'],
            [
                'name' => 'Funeral User',
                'password' => Hash::make('password'),
                'role' => User::ROLE_USER,
                'tenant_id' => $tenant?->id,
            ]
        );
    }
}
