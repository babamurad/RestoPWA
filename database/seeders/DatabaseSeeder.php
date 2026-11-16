<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        // Demo data and accounts with a well-known password ("password") are for development only
        if (app()->isProduction()) {
            $this->command?->error('DatabaseSeeder creates demo data and accounts with the password "password": it does not run in production.');

            return;
        }

        $this->call([
            DemoDataSeeder::class,
        ]);

        if (! User::where('email', 'admin@restopwa.local')->exists()) {
            User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@restopwa.local',
                'password' => 'password',
                'role' => UserRole::ADMIN,
            ]);
        }

        if (! User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test Client',
                'email' => 'test@example.com',
                'password' => 'password',
                'role' => UserRole::CLIENT,
            ]);
        }
    }
}
