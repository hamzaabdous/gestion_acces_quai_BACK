<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'Admin',
                'lastName' => 'Admin',
                'firstName' => 'Admin',
                'phoneNumber' => '0675',
                'password' => 'password123', // hashed by model
                'role' => 'ADMIN',
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'User',
                'lastName' => 'User',
                'firstName' => 'User',
                'phoneNumber' => '0675',
                'password' => 'password123',
                'role' => 'USER',
            ]
        );
    }
}
