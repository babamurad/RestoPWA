<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Vendor\Models\Restaurant;
use Database\Seeders\RestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_times_are_bare_ranges_because_the_app_adds_the_unit(): void
    {
        $this->seed(RestaurantSeeder::class);

        $times = Restaurant::withoutGlobalScopes()->pluck('delivery_time');

        $this->assertNotEmpty($times);
        foreach ($times as $time) {
            // "25-35", not "25-35 мин": the app prints "{time} мин" itself
            $this->assertMatchesRegularExpression('/^\d+(-\d+)?$/', (string) $time);
        }
    }
}
