<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Str;

use App\Modules\UserVessel\Models\UserVessel;
use App\Modules\UserVesselHistories\Models\UserVesselHistories;
use App\Modules\UserVesselBadges\Models\UserVesselBadges ;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
/*         User::firstOrCreate(
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
        ); */

        // Example companies, shifts, and workareas
        $companies = ['Tanger Alliance', 'APM Terminals', 'Marsa Maroc'];
        $shifts = ['A', 'B', 'C'];
        $workareas = ['Dock 1', 'Dock 2', 'Yard 3', 'Gate A'];

        // Create 10 user vessels
        for ($i = 1; $i <= 10; $i++) {

            $user = UserVessel::create([
                'matricule' => 'UV-' . date('ymd') . '-' . Str::upper(Str::random(4)),
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'function' => fake()->randomElement(['Operator', 'Technician', 'Supervisor', 'Developer']),
                'company' => fake()->randomElement($companies),
                'shift' => fake()->randomElement($shifts),
                'workarea' => fake()->randomElement($workareas),
            ]);

            // Each user has 1–3 histories
            $historyCount = rand(1, 3);

            for ($h = 1; $h <= $historyCount; $h++) {
                $history = UserVesselHistories::create([
                    'user_vessel_id' => $user->id,
                    'shift' => fake()->randomElement($shifts),
                    'work_date' => now()->subDays(rand(0, 10))->format('Y-m-d'),
                    'workarea' => fake()->randomElement($workareas),
                ]);

                // Each history has 1–4 badges
                $badgeCount = rand(1, 4);

                for ($b = 1; $b <= $badgeCount; $b++) {
                    UserVesselBadges ::create([
                        'user_vessel_history_id' => $history->id,
                        'badge_place' => fake()->randomElement(['Gate A', 'Gate B', 'Dock 1', 'Dock 2', 'Yard Entrance']),
                        'badge_date' => now()->subHours(rand(1, 48)),
                    ]);
                }
            }
        }

        $this->command->info('✅ User vessels, histories, and badges seeded successfully!');
    }
}
