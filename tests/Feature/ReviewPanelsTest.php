<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Filament\Resources\ReviewResource\Pages\ListReviews as AdminListReviews;
use App\Filament\Vendor\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Vendor\Resources\ReviewResource\Pages\ListReviews as VendorListReviews;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewPanelsTest extends TestCase
{
    use RefreshDatabase;

    private function makeReview(Restaurant $restaurant, int $rating = 5): Review
    {
        $order = Order::factory()->create([
            'vendor_id' => $restaurant->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        return Review::create([
            'order_id' => $order->id,
            'user_id' => User::factory()->create()->id,
            'restaurant_id' => $restaurant->id,
            'rating' => $rating,
            'comment' => 'Отзыв '.$rating,
        ]);
    }

    public function test_restaurateur_sees_only_own_restaurant_reviews_and_can_reply(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $own = $this->makeReview($restaurant, 4);
        $foreign = $this->makeReview(Restaurant::factory()->create(), 1);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();

        Livewire::test(VendorListReviews::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign])
            ->callTableAction('reply', $own, data: ['vendor_reply' => 'Спасибо за отзыв!'])
            ->assertHasNoTableActionErrors();

        $own->refresh();
        $this->assertSame('Спасибо за отзыв!', $own->vendor_reply);
        $this->assertNotNull($own->replied_at);
    }

    public function test_admin_can_hide_review_and_rating_is_recalculated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $restaurant = Restaurant::factory()->create();
        $this->makeReview($restaurant, 5);
        $bad = $this->makeReview($restaurant, 1);

        $this->assertEquals(3.0, $restaurant->fresh()->rating);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AdminListReviews::class)
            ->callTableAction('hide', $bad)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($bad->fresh()->is_hidden);
        $this->assertEquals(5.0, $restaurant->fresh()->rating);
        $this->assertSame(1, $restaurant->fresh()->review_count);
    }

    public function test_recalculate_command_replaces_hand_entered_ratings(): void
    {
        $withReviews = Restaurant::factory()->create();
        $this->makeReview($withReviews, 4);
        $this->makeReview($withReviews, 5);
        $withReviews->forceFill(['rating' => 1.0, 'review_count' => 999])->saveQuietly();
        $seeded = Restaurant::factory()->create(['rating' => 4.8, 'review_count' => 1247]);

        $this->artisan('reviews:recalculate-ratings')->assertSuccessful();

        $this->assertEquals(4.5, $withReviews->fresh()->rating);
        $this->assertSame(2, $withReviews->fresh()->review_count);
        $this->assertNull($seeded->fresh()->rating);
        $this->assertSame(0, $seeded->fresh()->review_count);
    }

    public function test_deleting_order_or_customer_recalculates_rating(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->makeReview($restaurant, 5);
        $viaOrder = $this->makeReview($restaurant, 1);
        $viaUser = $this->makeReview($restaurant, 2);
        $this->assertSame(3, $restaurant->fresh()->review_count);

        $viaOrder->order->delete();

        $this->assertDatabaseMissing('reviews', ['id' => $viaOrder->id]);
        $this->assertSame(2, $restaurant->fresh()->review_count);
        $this->assertEquals(3.5, $restaurant->fresh()->rating);

        $viaUser->user->delete();

        $this->assertDatabaseMissing('reviews', ['id' => $viaUser->id]);
        $this->assertSame(1, $restaurant->fresh()->review_count);
        $this->assertEquals(5.0, $restaurant->fresh()->rating);
    }

    public function test_vendor_bulk_order_deletion_recalculates_rating(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $this->makeReview($restaurant, 5);
        $bad = $this->makeReview($restaurant, 1);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();

        Livewire::test(ListOrders::class)
            ->callTableBulkAction('delete', [$bad->order]);

        $this->assertSame(1, $restaurant->fresh()->review_count);
        $this->assertEquals(5.0, $restaurant->fresh()->rating);
    }
}
