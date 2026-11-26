<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('booking.admin.email');
        $password = config('booking.admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Renseigner ADMIN_EMAIL et ADMIN_PASSWORD (12 caractères minimum) dans .env.');
        }

        // Ne modifie pas un compte existant : le mot de passe changé par l'hôte reste le sien.
        User::query()->firstOrCreate(['email' => $email], [
            'name' => config('booking.admin.name'),
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
