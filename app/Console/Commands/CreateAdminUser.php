<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email} {password} {--name=}';

    protected $description = 'Create an admin user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->option('name') ?? 'Admin';

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]);
            $this->info("Admin user '{$email}' updated successfully.");
        } else {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]);
            $this->info("Admin user '{$email}' created successfully.");
        }

        return Command::SUCCESS;
    }
}
