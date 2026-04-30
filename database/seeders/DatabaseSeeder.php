<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
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
        $this->call([
            RestaurantSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@restopwa.local',
            'password' => 'password',
            'role' => UserRole::ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => UserRole::CLIENT,
        ]);
    }
}
