<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\OrderCancelReason;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\BuildsRestaurants;
use Tests\DuskTestCase;

/** The restaurant's orders board and the admin panel. */
class PanelsTest extends DuskTestCase
{
    use BuildsRestaurants;
    use DatabaseTruncation;

    private User $owner;

    private Restaurant $restaurant;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makePerson(UserRole::RESTAURATEUR, 'Хозяин ресторана', 'owner@test.local');
        $this->restaurant = $this->makeRestaurant('Sakura Sushi', 'sakura-sushi', ['owner_id' => $this->owner->id]);
        $this->customer = $this->makePerson(UserRole::CLIENT, 'Айгуль', 'aigul@test.local');
    }

    private function kanbanUrl(): string
    {
        return '/vendor/'.$this->restaurant->id.'/kanban-orders';
    }

    #[Group('smoke')]
    public function test_owner_sees_a_new_order_highlighted_on_the_board(): void
    {
        $new = $this->makeOrder($this->restaurant, $this->customer, Order::STATUS_PENDING);
        $cooking = $this->makeOrder($this->restaurant, $this->customer, Order::STATUS_PREPARING);

        $this->browse(function (Browser $browser) use ($new, $cooking) {
            $browser->loginAs($this->owner)
                ->visit($this->kanbanUrl())
                ->waitForText('#'.$new->number)
                ->assertSee('#'.$cooking->number)
                // The order waiting for an answer stands out from those in work
                ->assertPresent('[data-column="pending"] .kb-card-new')
                ->assertMissing('[data-column="cooking"] .kb-card-new');
        });
    }

    #[Group('smoke')]
    public function test_owner_declines_an_order_with_a_reason(): void
    {
        $order = $this->makeOrder($this->restaurant, $this->customer, Order::STATUS_PENDING);

        $this->browse(function (Browser $browser) use ($order) {
            $browser->loginAs($this->owner)
                ->visit($this->kanbanUrl())
                ->waitForText('#'.$order->number)
                ->click('.kb-decline summary')
                ->waitFor('.kb-decline-form select');

            // Without a reason the button stays off
            $browser->assertDisabled('.kb-decline-form button')
                ->select('.kb-decline-form select', OrderCancelReason::OUT_OF_STOCK->value)
                ->waitUntilEnabled('.kb-decline-form button')
                ->click('.kb-decline-form button')
                ->waitUntilMissingText('#'.$order->number);
        });

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::CANCELLED_BY_RESTAURANT, $order->cancelled_by);
        $this->assertSame(OrderCancelReason::OUT_OF_STOCK, $order->cancel_reason);
    }

    public function test_owner_opens_the_panel_of_the_own_restaurant(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->owner)
                ->visit('/vendor')
                ->waitForText('Sakura Sushi')
                ->assertSee('Sakura Sushi');
        });
    }

    #[Group('smoke')]
    public function test_admin_opens_the_analytics_and_a_restaurateur_cannot(): void
    {
        $admin = $this->makePerson(UserRole::ADMIN, 'Главный админ', 'admin@test.local');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/analytics')
                ->waitForText('Аналитика')
                ->assertSee('Оборот');

            $browser->loginAs($this->owner)
                ->visit('/admin')
                ->assertSee('403');
        });
    }
}
