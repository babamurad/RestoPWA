<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\BuildsRestaurants;
use Tests\Browser\Concerns\UsesTheApp;
use Tests\DuskTestCase;

class TrackingAndProfileTest extends DuskTestCase
{
    use BuildsRestaurants;
    use DatabaseTruncation;
    use UsesTheApp;

    private Restaurant $restaurant;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = $this->makeRestaurant('Sakura Sushi', 'sakura-sushi');
        $this->customer = $this->makePerson(UserRole::CLIENT, 'Айгуль', 'aigul@test.local');
    }

    #[Group('smoke')]
    public function test_a_signed_tracking_link_shows_the_status_and_a_forged_one_is_refused(): void
    {
        $order = $this->makeOrder($this->restaurant, $this->customer, Order::STATUS_PREPARING);

        $signed = URL::temporarySignedRoute('order.track.guest', now()->addHour(), ['orderId' => $order->id]);

        $this->browse(function (Browser $browser) use ($order, $signed) {
            $browser->visit($signed)
                ->waitForText('Статус заказа')
                ->assertSee('Готовится');

            $browser->visit('/order/'.$order->id.'/track/guest')->assertSee('403');
            $browser->visit('/order/'.$order->id.'/track/guest?signature=invalid')->assertSee('403');
        });
    }

    public function test_only_the_owner_of_an_order_can_track_it_without_a_link(): void
    {
        $order = $this->makeOrder($this->restaurant, $this->customer, Order::STATUS_DELIVERING);
        $stranger = $this->makePerson(UserRole::CLIENT, 'Посторонний', 'stranger@test.local');

        $this->browse(function (Browser $browser) use ($order, $stranger) {
            // A guest is sent to sign in
            $browser->visit('/order/'.$order->id.'/track')->waitForLocation('/login');

            $browser->loginAs($this->customer)
                ->visit('/order/'.$order->id.'/track')
                ->waitForText('Статус заказа');

            $browser->loginAs($stranger)
                ->visit('/order/'.$order->id.'/track')
                ->assertSee('404');
        });
    }

    #[Group('smoke')]
    public function test_marketing_notifications_are_off_by_default_and_the_choice_is_kept(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signIn($browser, $this->customer)->waitForLocation('/profile');

            $browser->waitFor('@marketing-sms')
                // (the heading is printed in capitals, so the section is found by its label)
                ->assertPresent('section[aria-label="Что вам присылают"]')
                // Order notifications are not a choice
                ->assertSee('Всегда')
                ->assertAttribute('@marketing-sms', 'aria-checked', 'false')
                ->click('@marketing-sms')
                ->waitUntil('document.querySelector(\'[dusk="marketing-sms"]\').getAttribute("aria-checked") === "true"')
                ->refresh()
                ->waitFor('@marketing-sms')
                ->pause(500)
                ->assertAttribute('@marketing-sms', 'aria-checked', 'true')
                ->assertAttribute('@marketing-email', 'aria-checked', 'false');
        });

        $this->assertSame(true, $this->customer->fresh()->notification_preferences['marketing']['sms']);
    }
}
