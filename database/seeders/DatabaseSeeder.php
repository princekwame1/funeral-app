<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@funeral.test'],
            [
                'name' => 'Funeral Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@funeral.test'],
            [
                'name' => 'Funeral User',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );
    }
}
