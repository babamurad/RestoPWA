<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domains\Order\Models\Order;
use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurant;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::factory()->create(['rating' => null, 'review_count' => 0]);
        $this->customer = User::factory()->create(['name' => 'Мерген Аманов', 'phone' => '+99361234567']);
    }

    private function deliveredOrder(?User $user = null, string $status = Order::STATUS_DELIVERED): Order
    {
        return Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => ($user ?? $this->customer)->id,
            'status' => $status,
        ]);
    }

    private function review(Order $order, array $payload = ['rating' => 5, 'comment' => 'Вкусно']): TestResponse
    {
        return $this->actingAs($this->customer)->postJson("/api/v1/orders/{$order->id}/review", $payload);
    }

    public function test_customer_can_review_delivered_order(): void
    {
        $order = $this->deliveredOrder();

        $this->review($order)->assertCreated()->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'user_id' => $this->customer->id,
            'restaurant_id' => $this->restaurant->id,
            'rating' => 5,
        ]);
    }

    public function test_completed_pickup_order_can_be_reviewed(): void
    {
        $this->review($this->deliveredOrder(status: 'completed'))->assertCreated();
    }

    public function test_order_cannot_be_reviewed_before_completion(): void
    {
        foreach ([Order::STATUS_PENDING, Order::STATUS_DELIVERING, Order::STATUS_CANCELLED] as $status) {
            $this->review($this->deliveredOrder(status: $status))->assertStatus(422);
        }

        $this->assertSame(0, Review::count());
    }

    public function test_foreign_order_cannot_be_reviewed(): void
    {
        $order = $this->deliveredOrder(User::factory()->create());

        $this->review($order)->assertNotFound();
        $this->assertSame(0, Review::count());
    }

    public function test_order_can_be_reviewed_only_once(): void
    {
        $order = $this->deliveredOrder();

        $this->review($order)->assertCreated();
        $this->review($order, ['rating' => 1])->assertStatus(409);

        $this->assertSame(5, Review::firstOrFail()->rating);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $order = $this->deliveredOrder();

        $this->review($order, ['rating' => 0])->assertStatus(422);
        $this->review($order, ['rating' => 6])->assertStatus(422);
        $this->review($order, ['comment' => 'без оценки'])->assertStatus(422);
    }

    public function test_guest_cannot_review(): void
    {
        $order = $this->deliveredOrder();

        $this->postJson("/api/v1/orders/{$order->id}/review", ['rating' => 5])->assertUnauthorized();
    }

    public function test_restaurant_rating_is_recalculated_from_visible_reviews(): void
    {
        $this->review($this->deliveredOrder(), ['rating' => 5])->assertCreated();
        $this->review($this->deliveredOrder(), ['rating' => 4])->assertCreated();
        $this->review($this->deliveredOrder(), ['rating' => 4])->assertCreated();

        $this->restaurant->refresh();
        $this->assertSame(3, $this->restaurant->review_count);
        $this->assertEquals(4.3, $this->restaurant->rating);

        // Moderation: a hidden review leaves the rating
        Review::where('rating', 5)->firstOrFail()->update(['is_hidden' => true]);

        $this->restaurant->refresh();
        $this->assertSame(2, $this->restaurant->review_count);
        $this->assertEquals(4.0, $this->restaurant->rating);
    }

    public function test_public_list_shows_visible_reviews_with_first_name_only(): void
    {
        $this->review($this->deliveredOrder(), ['rating' => 5, 'comment' => 'Отлично'])->assertCreated();
        $hidden = $this->deliveredOrder();
        $this->review($hidden, ['rating' => 1, 'comment' => 'Спам'])->assertCreated();
        Review::where('order_id', $hidden->id)->update(['is_hidden' => true]);

        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->slug}/reviews");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'Отлично')
            ->assertJsonPath('data.0.author', 'Мерген');
        $this->assertStringNotContainsString('Аманов', $response->getContent());
        $this->assertStringNotContainsString('+99361234567', $response->getContent());
    }

    public function test_orders_list_tells_whether_order_can_be_reviewed(): void
    {
        $reviewable = $this->deliveredOrder();
        $reviewed = $this->deliveredOrder();
        $inProgress = $this->deliveredOrder(status: Order::STATUS_PREPARING);
        $this->review($reviewed, ['rating' => 3])->assertCreated();

        $orders = collect($this->actingAs($this->customer)->getJson('/api/v1/orders')->json('data'))->keyBy('id');

        $this->assertTrue($orders[$reviewable->id]['can_review']);
        $this->assertFalse($orders[$reviewed->id]['can_review']);
        $this->assertSame(3, $orders[$reviewed->id]['review']['rating']);
        $this->assertFalse($orders[$inProgress->id]['can_review']);
    }
}
