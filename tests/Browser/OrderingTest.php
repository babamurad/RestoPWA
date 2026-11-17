<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\BuildsRestaurants;
use Tests\Browser\Concerns\UsesTheApp;
use Tests\DuskTestCase;

/**
 * The customer's way to an order: choose a dish and its modifiers, the cart, signing in,
 * checkout (pickup: the delivery map needs an external service), cancelling.
 */
class OrderingTest extends DuskTestCase
{
    use BuildsRestaurants;
    use DatabaseTruncation;
    use UsesTheApp;

    private Restaurant $restaurant;

    private Product $pizza;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = $this->makeRestaurant('Pepperoni Pizza', 'pepperoni-pizza');
        $this->pizza = $this->makePizza($this->restaurant);
        $this->customer = $this->makePerson(UserRole::CLIENT, 'Айгуль', 'aigul@test.local');
    }

    #[Group('smoke')]
    #[Group('merge-gate')]
    public function test_a_required_modifier_must_be_chosen_and_each_choice_is_its_own_cart_line(): void
    {
        $this->browse(function (Browser $browser) {
            $this->openDish($browser, 'pepperoni-pizza', $this->pizza);

            // The size is required and has no default: the button waits for a choice
            $browser->assertDisabled('@add-to-cart')
                ->assertSee('Выберите один')
                ->click($this->optionDusk($this->pizza, 'Размер', 'Большая'))
                ->waitUntilEnabled('@add-to-cart')
                ->assertSeeIn('@add-to-cart', '150')
                ->click($this->optionDusk($this->pizza, 'Топпинг', 'Сыр'))
                ->assertSeeIn('@add-to-cart', '180')
                ->click('@add-to-cart');

            // The same dish with another choice is a separate line
            $this->openDish($browser, 'pepperoni-pizza', $this->pizza);
            $browser->click($this->optionDusk($this->pizza, 'Размер', 'Маленькая'))
                ->click('@add-to-cart');

            $browser->visit('/cart')
                ->waitFor('@cart-line')
                ->assertSee('Большая, Сыр')
                ->assertSee('Маленькая');
            $this->assertCount(2, $browser->elements('[dusk="cart-line"]'));
        });
    }

    public function test_a_group_that_reached_its_maximum_blocks_further_choices(): void
    {
        $this->browse(function (Browser $browser) {
            $this->openDish($browser, 'pepperoni-pizza', $this->pizza);

            $browser->click($this->optionDusk($this->pizza, 'Топпинг', 'Сыр'))
                ->click($this->optionDusk($this->pizza, 'Топпинг', 'Грибы'))
                ->assertDisabled($this->optionDusk($this->pizza, 'Топпинг', 'Бекон'))
                // Un-choosing one frees the place
                ->click($this->optionDusk($this->pizza, 'Топпинг', 'Сыр'))
                ->waitUntilEnabled($this->optionDusk($this->pizza, 'Топпинг', 'Бекон'));
        });
    }

    #[Group('smoke')]
    #[Group('merge-gate')]
    public function test_a_guest_is_sent_to_sign_in_and_comes_back_to_the_checkout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->openDish($browser, 'pepperoni-pizza', $this->pizza);
            $browser->click($this->optionDusk($this->pizza, 'Размер', 'Маленькая'))
                ->click('@add-to-cart')
                ->visit('/cart')
                ->waitFor('@cart-checkout')
                ->click('@cart-checkout')
                ->waitForLocation('/login')
                ->assertQueryStringHas('redirect', '/checkout');

            // Signing in leads on to where the guest was going
            $browser->waitFor('@login-email')
                ->type('@login-email', $this->customer->email)
                ->type('@login-password', self::PASSWORD)
                ->click('@login-submit')
                ->waitForLocation('/checkout')
                ->waitForText('Оформление заказа');
        });
    }

    #[Group('smoke')]
    #[Group('merge-gate')]
    public function test_a_customer_places_a_pickup_order_and_then_cancels_it(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signIn($browser, $this->customer)->waitForLocation('/profile');

            $this->openDish($browser, 'pepperoni-pizza', $this->pizza);
            $browser->click($this->optionDusk($this->pizza, 'Размер', 'Большая'))
                ->click($this->optionDusk($this->pizza, 'Топпинг', 'Сыр'))
                ->click('@qty-plus')
                ->click('@add-to-cart')
                ->visit('/cart')
                ->waitFor('@cart-checkout')
                ->click('@cart-checkout')
                ->waitForText('Оформление заказа');

            // Step 1: pickup, a phone number
            $browser->click('@delivery-pickup')
                ->type('@checkout-phone', '+99361234567')
                ->waitUntilEnabled('@checkout-address-next')
                ->click('@checkout-address-next');

            // Step 2: cash is the only method and already chosen
            $browser->waitFor('@checkout-payment-next')
                ->click('@checkout-payment-next');

            // Step 3: confirm
            $browser->waitFor('@checkout-submit')
                ->click('@checkout-submit')
                ->waitForText('Заказ оформлен!', 30);
        });

        // What the kitchen got: pickup, cash, and the price of the choice from the menu: (100 + 50 + 30) × 2
        $order = Order::sole();
        $this->assertSame(Order::DELIVERY_TYPE_PICKUP, $order->delivery_type);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame($this->customer->id, $order->user_id);
        $this->assertEquals(360.0, $order->total);
        $this->assertSame(['Большая', 'Сыр'], array_column($order->items[0]['modifiers'], 'name'));

        $this->browse(function (Browser $browser) use ($order) {
            $browser->visit('/orders')
                ->waitFor('@order-'.$order->id)
                ->assertSeeIn('@order-'.$order->id, 'Pepperoni Pizza')
                ->click('@cancel-order')
                ->waitFor('.swal2-confirm')
                ->click('.swal2-confirm')
                ->waitUntilMissing('@cancel-order');

            // The status badge is printed in capitals, so the text is compared without case
            $browser->waitUsing(5, 100, fn () => str_contains(
                mb_strtolower($browser->text('@order-'.$order->id)),
                'отменён',
            ));
        });

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame(Order::CANCELLED_BY_CUSTOMER, $order->cancelled_by);
    }

    public function test_an_order_the_kitchen_has_started_cannot_be_cancelled_by_the_customer(): void
    {
        $order = $this->makeOrder($this->restaurant, $this->customer, Order::STATUS_PREPARING);

        $this->browse(function (Browser $browser) use ($order) {
            $this->signIn($browser, $this->customer)->waitForLocation('/profile');

            $browser->visit('/orders')
                ->waitFor('@order-'.$order->id)
                ->assertMissing('@cancel-order');
        });
    }

    public function test_switching_to_another_restaurant_asks_before_emptying_the_cart(): void
    {
        $other = $this->makeRestaurant('Burger House', 'burger-house');
        $burger = $this->makeDish($other, 'Бургер', 12.0);

        $this->browse(function (Browser $browser) use ($burger) {
            $this->openDish($browser, 'pepperoni-pizza', $this->pizza);
            $browser->click($this->optionDusk($this->pizza, 'Размер', 'Маленькая'))
                ->click('@add-to-cart');

            $this->openDish($browser, 'burger-house', $burger);
            $browser->click('@add-to-cart')
                ->waitFor('.swal2-confirm')
                ->assertSee('Сменить ресторан?')
                ->click('.swal2-confirm')
                ->visit('/cart')
                ->waitFor('@cart-line')
                ->assertSee('Бургер')
                ->assertDontSee('Пепперони');
        });
    }
}
