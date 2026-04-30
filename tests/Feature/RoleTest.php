<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_admin_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isRestaurateur());
        $this->assertFalse($user->isClient());
    }

    public function test_user_can_have_restaurateur_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isRestaurateur());
        $this->assertFalse($user->isClient());
    }

    public function test_user_can_have_client_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isRestaurateur());
        $this->assertTrue($user->isClient());
    }

    public function test_user_defaults_to_client_role(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($user->isClient());
    }
}
