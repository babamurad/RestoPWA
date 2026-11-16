<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use App\Services\Account\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $sushi;

    private Restaurant $grill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sushi = Restaurant::factory()->create(['name' => 'Sakura Sushi', 'description' => 'Rolls and ramen', 'tags' => ['sushi'], 'is_active' => true]);
        $this->grill = Restaurant::factory()->create(['name' => 'Altyn Grill', 'description' => 'Kebab house', 'tags' => ['grill', 'national'], 'is_active' => true]);

        Product::factory()->create(['vendor_id' => $this->grill->id, 'name' => 'Lamb Shashlik', 'is_available' => true]);
        Product::factory()->create(['vendor_id' => $this->sushi->id, 'name' => 'Secret Burger', 'is_available' => false]);
    }

    private function ids(string $query): array
    {
        return collect($this->getJson('/api/v1/restaurants'.$query)->assertOk()->json())->pluck('id')->all();
    }

    public function test_list_without_filters_returns_all_active_restaurants_with_tags(): void
    {
        $hidden = Restaurant::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/restaurants')->assertOk();
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($this->sushi->id) && $ids->contains($this->grill->id));
        $this->assertFalse($ids->contains($hidden->id));
        $this->assertSame(['sushi'], collect($response->json())->firstWhere('id', $this->sushi->id)['tags']);
    }

    public function test_search_matches_name_description_and_dishes_ignoring_case(): void
    {
        $this->assertSame([$this->sushi->id], $this->ids('?search=sakura'));
        $this->assertSame([$this->grill->id], $this->ids('?search=KEBAB'));
        $this->assertSame([$this->grill->id], $this->ids('?search=shashlik'));
        $this->assertSame([], $this->ids('?search=lagman'));
    }

    public function test_search_ignores_unavailable_dishes_and_treats_wildcards_as_text(): void
    {
        $this->assertSame([], $this->ids('?search=secret'));
        $this->assertSame([], $this->ids('?search=%25'));
        $this->assertSame([], $this->ids('?search=_'));
    }

    public function test_filter_by_cuisine_tag(): void
    {
        $this->assertSame([$this->grill->id], $this->ids('?tag=national'));
        $this->assertSame([$this->sushi->id], $this->ids('?tag=sushi'));
        $this->assertSame([], $this->ids('?tag=pizza'));
    }

    public function test_customer_adds_and_removes_a_favourite(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/profile/favorites/{$this->sushi->id}")
            ->assertOk()->assertJsonPath('data.is_favorite', true);
        // Adding twice keeps one record
        $this->actingAs($user)->putJson("/api/v1/profile/favorites/{$this->sushi->id}")->assertOk();

        $this->actingAs($user)->getJson('/api/v1/profile/favorites')
            ->assertOk()->assertExactJson(['success' => true, 'data' => [$this->sushi->id], 'message' => null]);

        $this->actingAs($user)->deleteJson("/api/v1/profile/favorites/{$this->sushi->id}")->assertOk();
        $this->actingAs($user)->getJson('/api/v1/profile/favorites')->assertJsonCount(0, 'data');
    }

    public function test_favourites_are_private_and_need_a_real_restaurant(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->putJson("/api/v1/profile/favorites/{$this->sushi->id}")->assertOk();

        $this->actingAs($other)->getJson('/api/v1/profile/favorites')->assertJsonCount(0, 'data');
        $this->actingAs($other)->putJson('/api/v1/profile/favorites/'.fake()->uuid())->assertStatus(404);
        $this->actingAs($other)->putJson('/api/v1/profile/favorites/not-a-uuid')->assertStatus(404);
    }

    public function test_guest_has_no_favourites(): void
    {
        $this->getJson('/api/v1/profile/favorites')->assertStatus(401);
        $this->putJson("/api/v1/profile/favorites/{$this->sushi->id}")->assertStatus(401);
    }

    public function test_deleting_the_account_removes_the_favourites(): void
    {
        $user = User::factory()->create();
        $user->favoriteRestaurants()->attach($this->sushi->id);

        app(AccountDeletionService::class)->delete($user);

        $this->assertDatabaseMissing('user_favorites', ['user_id' => $user->id]);
    }
}
