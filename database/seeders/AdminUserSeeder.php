<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin is God',
            'email' => 'gyad@damn.it',
            'password' => Hash::make('passpassword'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
