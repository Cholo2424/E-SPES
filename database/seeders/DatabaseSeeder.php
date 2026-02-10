<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test coordinator user
        User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@espes.local',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create additional test users
        User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@espes.local',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@espes.local',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create an inactive user (for testing account lockout)
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@espes.local',
            'password' => Hash::make('password123'),
            'role' => 'coordinator',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        // Create a non-coordinator user (for testing role restriction)
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@espes.local',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Test users created successfully!');
        $this->command->info('-------------------------------------');
        $this->command->info('Coordinator User:');
        $this->command->info('Email: coordinator@espes.local');
        $this->command->info('Password: password123');
        $this->command->info('-------------------------------------');
    }
}
