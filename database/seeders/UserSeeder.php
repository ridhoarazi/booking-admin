<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@booking.test'],
            [
                'name' => 'Administrator',
                'phone' => '081200000001',
                'password' => 'password',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@booking.test'],
            [
                'name' => 'Demo User',
                'phone' => '081200000002',
                'password' => 'password',
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ],
        );
    }
}
