<?php

namespace Tests\Browser;

use App\Models\User;
use App\Domains\Order\Models\Order;
use Database\Factories\RestaurantFactory;
use Database\Factories\ProductFactory;
use Database\Factories\CategoryFactory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;
use App\Enums\UserRole;

class RestaurateurRoleTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $restaurant;
    protected $vendorUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->vendorUser = User::factory()->create([
            'role' => UserRole::RESTAURATEUR,
            'name' => 'Test Vendor',
            'email' => 'vendor@test.com',
            'password' => bcrypt('password')
        ]);

        $this->restaurant = RestaurantFactory::new()->create([
            'slug' => 'test-vendor', 
            'is_active' => true,
            'owner_id' => $this->vendorUser->id,
            'vendor_id' => $this->vendorUser->id
        ]);
    }

    /**
     * VEN-01: Restaurateur can access vendor panel and see their restaurant
     */
    #[Group('vendor-role')]
    public function test_vendor_can_access_panel(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->vendorUser)
                    ->visit('/vendor')
                    ->waitForText($this->restaurant->name, 15)
                    ->assertSee($this->restaurant->name);
        });
    }

    /**
     * VEN-02: Restaurateur can view orders in kanban
     */
    #[Group('vendor-role')]
    public function test_vendor_can_view_kanban(): void
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'status' => 'pending',
            'address' => ['address' => 'Test Address'],
            'total' => 1000
        ]);

        $this->browse(function (Browser $browser) use ($order) {
            $browser->loginAs($this->vendorUser)
                    ->visit('/vendor/orders/kanban')
                    // Wait for kanban to load
                    ->waitForText('#' . substr($order->id, -6), 10);
        });
    }
}
