<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. AKUN ADMIN
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@bact.com',
            'phone_number' => '080000000000',
            'nik' => '0000000000000000',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // 2. AKUN SEMENTARA (Bypass Profil)
        User::create([
            'name' => 'Akrimna Fahma',
            'email' => 'akrimnafhm@gmail.com',
            'phone_number' => '081234567891',
            'nik' => '1234567890123456',
            'password' => Hash::make('password123'),
            'role' => 'peserta',
            'email_verified_at' => now(), 
            'phone_verified_at' => now(), 
        ]);
    }
}