<?php

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_mode_takes_no_commission(): void
    {
        config(['billing.mode' => 'subscription']);
        $restaurant = Restaurant::factory()->create(['commission_percent' => 10]);
        $order = Order::factory()->create(['vendor_id' => $restaurant->id]);

        $this->assertSame(0.0, app(\App\Domains\Payment\Services\CommissionCalculator::class)->amount($order));
    }

    public function test_subscription_mode_generates_no_settlements(): void
    {
        config(['billing.mode' => 'subscription']);
        Restaurant::factory()->create();

        $this->artisan('settlements:generate')->assertSuccessful();

        $this->assertDatabaseCount('vendor_settlements', 0);
    }

    public function test_subscription_activity_follows_paid_until(): void
    {
        $restaurant = Restaurant::factory()->create(['subscription_plan' => 'medium']);
        $this->assertFalse($restaurant->hasActiveSubscription());
        $this->assertSame(600.0, $restaurant->subscriptionPrice());

        $restaurant->update(['subscription_paid_until' => today()]);
        $this->assertTrue($restaurant->fresh()->hasActiveSubscription());

        $restaurant->update(['subscription_paid_until' => today()->subDay()]);
        $this->assertFalse($restaurant->fresh()->hasActiveSubscription());
    }
}
