<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\VendorSettlement;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    private function production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->production();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must not run in production');

        (new DemoDataSeeder)->run();
    }

    public function test_database_seeder_creates_no_known_password_accounts_in_production(): void
    {
        $this->production();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin@restopwa.local']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertDatabaseCount('users', 0);
    }

    /** @return array{demo: User, real: User, restaurant: Restaurant, demoOrder: Order, realOrder: Order} */
    private function demoAndRealData(): array
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'sakura-sushi']);
        $demo = User::factory()->create(['email' => 'demo.client1@restopwa.local', 'phone' => '+99361000001']);
        $real = User::factory()->create(['email' => 'aigul@example.com']);

        $demoOrder = Order::factory()->create(['vendor_id' => $restaurant->id, 'user_id' => $demo->id, 'status' => Order::STATUS_DELIVERED]);
        $realOrder = Order::factory()->create(['vendor_id' => $restaurant->id, 'user_id' => $real->id, 'status' => Order::STATUS_DELIVERED]);

        Review::create(['order_id' => $demoOrder->id, 'user_id' => $demo->id, 'restaurant_id' => $restaurant->id, 'rating' => 1]);
        Review::create(['order_id' => $realOrder->id, 'user_id' => $real->id, 'restaurant_id' => $restaurant->id, 'rating' => 5]);

        $courierUser = User::factory()->create(['email' => 'demo.courier1@restopwa.local', 'role' => UserRole::COURIER]);
        Courier::create(['user_id' => $courierUser->id]);

        DB::table('order_rejections')->insert([
            ['reason' => 'network', 'vendor_id' => $restaurant->id, 'user_id' => $demo->id, 'created_at' => now()],
            ['reason' => 'network', 'vendor_id' => $restaurant->id, 'user_id' => $real->id, 'created_at' => now()],
        ]);
        DB::table('sms_logs')->insert([
            ['id' => (string) Str::uuid(), 'phone' => '+99361000001', 'message' => 'x', 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'phone' => '+99365999999', 'message' => 'x', 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // A settlement of the real order is kept; an empty one (the seeder makes those) is demo
        $real_settlement = VendorSettlement::create(['restaurant_id' => $restaurant->id, 'period_from' => now()->subWeek(), 'period_to' => now(), 'gross_amount' => 1, 'commission_amount' => 0, 'net_payable' => 1, 'status' => 'draft']);
        $realOrder->forceFill(['vendor_settlement_id' => $real_settlement->id])->saveQuietly();
        VendorSettlement::create(['restaurant_id' => $restaurant->id, 'period_from' => now()->subWeeks(2), 'period_to' => now()->subWeek(), 'gross_amount' => 1, 'commission_amount' => 0, 'net_payable' => 1, 'status' => 'paid']);

        return compact('demo', 'real', 'restaurant', 'demoOrder', 'realOrder');
    }

    public function test_purge_without_force_only_reports(): void
    {
        $data = $this->demoAndRealData();

        $this->artisan('demo:purge')->expectsOutputToContain('Ничего не удалено')->assertSuccessful();

        $this->assertModelExists($data['demo']);
        $this->assertModelExists($data['demoOrder']);
    }

    public function test_purge_removes_demo_data_and_keeps_real_data(): void
    {
        $data = $this->demoAndRealData();

        $this->artisan('demo:purge --force')->assertSuccessful();

        $this->assertModelMissing($data['demo']);
        $this->assertModelMissing($data['demoOrder']);
        $this->assertDatabaseMissing('users', ['email' => 'demo.courier1@restopwa.local']);
        $this->assertDatabaseCount('courier_profiles', 0);
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('order_rejections', 1);
        $this->assertDatabaseMissing('sms_logs', ['phone' => '+99361000001']);
        $this->assertDatabaseHas('sms_logs', ['phone' => '+99365999999']);
        $this->assertDatabaseCount('vendor_settlements', 1);

        // Real customer, order and review stay; the restaurant's rating no longer counts the demo review
        $this->assertModelExists($data['real']);
        $this->assertModelExists($data['realOrder']);
        $this->assertModelExists($data['restaurant']);
        $this->assertEquals(5.0, (float) $data['restaurant']->fresh()->rating);
    }

    public function test_purge_keeps_seeded_restaurants_unless_asked_and_spares_those_with_real_orders(): void
    {
        $data = $this->demoAndRealData();

        $this->artisan('demo:purge --force')->assertSuccessful();
        $this->assertModelExists($data['restaurant']);

        // Asked to remove the restaurants too: this one has an order of a real customer, so it stays
        $this->artisan('demo:purge --force --include-restaurants')->assertSuccessful();
        $this->assertModelExists($data['restaurant']);

        $data['realOrder']->delete();
        $this->artisan('demo:purge --force --include-restaurants')->assertSuccessful();
        $this->assertModelMissing($data['restaurant']);
    }
}
