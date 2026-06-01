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
        User::create([
            'name' => 'Admin Taktik Ujian',
            'email' => 'admin@taktikujian.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Create Regular Test User
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->call([
            DummyDataSeeder::class
        ]);
    }
}
