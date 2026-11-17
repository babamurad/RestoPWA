<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Order\Models\Order;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\BuildsRestaurants;
use Tests\Browser\Concerns\UsesTheApp;
use Tests\DuskTestCase;

class CourierFlowTest extends DuskTestCase
{
    use BuildsRestaurants;
    use DatabaseTruncation;
    use UsesTheApp;

    #[Group('smoke')]
    public function test_courier_goes_on_shift_takes_an_order_and_delivers_it(): void
    {
        $restaurant = $this->makeRestaurant('Sakura Sushi', 'sakura-sushi');
        $customer = $this->makePerson(UserRole::CLIENT, 'Айгуль', 'aigul@test.local');
        $courier = $this->makeCourier();
        $order = $this->makeOrder($restaurant, $customer, Order::STATUS_READY_FOR_PICKUP, ['total' => 125.0, 'delivery_fee' => 15.0]);

        $this->browse(function (Browser $browser) use ($courier, $order) {
            // A courier lands on the work screen after signing in
            $this->signIn($browser, $courier->user)
                ->waitForLocation('/courier')
                ->waitFor('@courier-shift')
                ->assertSee('Не на смене')
                ->assertDontSee('ул. Битарап 10')
                ->click('@courier-shift')
                ->waitForText('На смене');

            // Before accepting he sees the street and what he earns, not who ordered
            $browser->waitFor('@courier-take-order')
                ->assertSee('ул. Битарап 10')
                ->assertSee('Вам за доставку: 15 TMT')
                ->assertDontSee($order->user->phone)
                ->click('@courier-take-order')
                ->waitFor('@courier-delivered')
                ->assertSee('Взять наличными: 125 TMT');

            // The shift cannot end with an order on the way
            $browser->click('@courier-shift')
                ->waitForText('Сначала доставьте заказ');

            $browser->click('@courier-delivered')
                ->acceptDialog()
                ->waitUntilMissing('@courier-delivered')
                ->waitForText('Сегодня');
        });

        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertSame($courier->id, $order->fresh()->courier_id);
        $this->assertEquals(15.0, CourierEarning::sole()->amount);
        $this->assertSame(Courier::STATUS_AVAILABLE, $courier->fresh()->status);
    }

    public function test_a_customer_cannot_open_the_courier_screen(): void
    {
        $customer = $this->makePerson(UserRole::CLIENT, 'Айгуль', 'aigul@test.local');

        $this->browse(function (Browser $browser) use ($customer) {
            $this->signIn($browser, $customer)->waitForLocation('/profile');

            $browser->visit('/courier')
                ->waitForLocation('/');
        });
    }
}
