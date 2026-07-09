<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;
use App\Enums\UserRole;

class AdminRoleTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);
    }

    /**
     * ADM-01: Admin can access admin panel
     */
    #[Group('admin-role')]
    public function test_admin_can_access_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin')
                    ->pause(1500)
                    ->assertSee($this->adminUser->name); // Usually name is visible in header
        });
    }
}
