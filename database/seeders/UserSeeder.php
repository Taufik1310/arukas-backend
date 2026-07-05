<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Administrator', 'email' => 'admin@arukas.com', 'password' => Hash::make('admin123'), 'role' => 'admin', 'phone' => '081234567890', 'is_active' => true]);
        User::create(['name' => 'Petugas Kasir', 'email' => 'petugas@arukas.com', 'password' => Hash::make('petugas123'), 'role' => 'petugas', 'phone' => '089876543210', 'is_active' => true]);
        User::factory(5)->create();
    }
}
