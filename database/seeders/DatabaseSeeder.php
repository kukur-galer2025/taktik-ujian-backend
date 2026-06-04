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
        // User::factory(10)->create();

        // Create Admin User
        User::firstOrCreate(
            ['email' => 'admin@taktikujian.com'],
            [
                'name' => 'Admin Taktik Ujian',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // Create Regular Test User
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );

        $this->call([
            DummyDataSeeder::class
        ]);
    }
}
