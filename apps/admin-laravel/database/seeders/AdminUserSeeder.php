<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@yfd.test');
        $password = env('ADMIN_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => env('ADMIN_NAME', 'YFD Administrator'),
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info("Admin siap login → email: {$email}  password: {$password}");
    }
}
