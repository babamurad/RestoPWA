<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Services\GeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

/** Each sensitive path has its own limit: using one up never blocks the others. */
class RateLimitIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('geo:127.0.0.1');
        $this->instance(GeoService::class, Mockery::mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('reverseGeocode')->andReturn(null);
        }));
    }

    public function test_geo_lookups_are_limited_with_a_translated_429(): void
    {
        for ($i = 0; $i < 90; $i++) {
            $this->postJson('/api/v1/geo/reverse', ['lat' => 39.1, 'lon' => 63.5])->assertOk();
        }

        $this->postJson('/api/v1/geo/reverse', ['lat' => 39.1, 'lon' => 63.5])
            ->assertStatus(429)
            ->assertJsonPath('message', __('api.too_many_requests'));
    }

    public function test_a_busy_map_does_not_use_up_the_limit_of_the_rest_of_the_api(): void
    {
        for ($i = 0; $i < 91; $i++) {
            $this->postJson('/api/v1/geo/reverse', ['lat' => 39.1, 'lon' => 63.5]);
        }

        // 91 geo calls in a minute (over the shared 60 of the old setup): the menu still answers
        $this->getJson('/api/v1/restaurants')->assertOk();
        $this->getJson('/api/v1/categories')->assertOk();
    }

    public function test_the_shared_api_limit_still_applies_to_other_routes(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/categories')->assertOk();
        }

        $this->getJson('/api/v1/categories')->assertStatus(429);
        // ...and the geo limit is a separate counter
        $this->postJson('/api/v1/geo/reverse', ['lat' => 39.1, 'lon' => 63.5])->assertOk();
    }

    public function test_login_attempts_do_not_use_up_the_shared_limit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/v1/login', ['email' => "user{$i}@example.com", 'password' => 'wrong']);
        }

        // 20 attempts (each on its own account, so none is blocked) cost the shared counter nothing
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/categories')->assertOk();
        }
    }
}
