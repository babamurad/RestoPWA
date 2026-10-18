<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Account\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A customer deletes their own account: personal data is wiped, the orders
 * restaurants rely on and the reviews behind their ratings stay.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Ага Агаев',
            'email' => 'aga@example.com',
            'phone' => '+99361234567',
            'password' => 'secret-pass',
        ]);
    }

    private function finishedOrder(array $attributes = []): Order
    {
        return Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => Order::STATUS_DELIVERED,
            'address' => ['address' => 'ул. Магтымгулы 1', 'name' => 'Ага', 'phone' => '+99361234567', 'lat' => 37.95, 'lon' => 58.38, 'apartment' => '12'],
            'comment' => 'Позвоните у двери',
        ] + $attributes);
    }

    public function test_customer_deletes_account_and_personal_data_is_wiped(): void
    {
        $order = $this->finishedOrder();
        $restaurant = Restaurant::findOrFail($order->vendor_id);
        Review::create(['order_id' => $order->id, 'user_id' => $this->user->id, 'restaurant_id' => $restaurant->id, 'rating' => 5, 'comment' => 'Вкусно']);
        UserAddress::create(['user_id' => $this->user->id, 'full_address' => 'ул. Магтымгулы 1']);
        SupportTicket::create(['user_id' => $this->user->id, 'message' => 'Где мой заказ?']);
        DB::table('sms_logs')->insert(['id' => fake()->uuid(), 'phone' => '+99361234567', 'message' => 'RestoPWA: Ваш заказ доставлен', 'status' => 'sent']);

        $this->actingAs($this->user)
            ->deleteJson('/api/v1/profile', ['password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = $this->user->fresh();
        $this->assertNotNull($user->deleted_at);
        $this->assertSame(AccountDeletionService::ANONYMOUS_NAME, $user->name);
        $this->assertNull($user->phone);
        $this->assertNull($user->password);
        $this->assertNotSame('aga@example.com', $user->email);
        $this->assertSame(0, UserAddress::where('user_id', $user->id)->count());
        $this->assertSame(0, SupportTicket::where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('sms_logs')->where('phone', '+99361234567')->count());

        // The restaurant keeps the order (money, history) without the customer's contacts
        $order->refresh();
        $this->assertSame(Order::STATUS_DELIVERED, $order->status);
        $this->assertNull($order->customer_name);
        $this->assertNull($order->customer_phone);
        $this->assertNull($order->customer_address);
        $this->assertNull($order->address['lat']);
        $this->assertNull($order->comment);

        // The rating is not affected; the author is anonymous
        $this->assertSame(1, Review::where('restaurant_id', $restaurant->id)->count());
        $this->assertEquals(5.0, (float) $restaurant->fresh()->rating);
        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/reviews")
            ->assertJsonPath('data.0.author', null);

        // Signed out and unable to sign back in
        $this->assertGuest('web');
        $this->postJson('/api/v1/login', ['email' => 'aga@example.com', 'password' => 'secret-pass'])
            ->assertStatus(401);
    }

    public function test_email_and_phone_can_be_registered_again(): void
    {
        $this->actingAs($this->user)->deleteJson('/api/v1/profile', ['password' => 'secret-pass'])->assertOk();

        $this->postJson('/api/v1/register', [
            'name' => 'Новый',
            'email' => 'aga@example.com',
            'phone' => '+99361234567',
            'password' => 'another-pass',
            'password_confirmation' => 'another-pass',
            'terms_accepted' => true,
        ])->assertCreated();
    }

    public function test_wrong_password_keeps_the_account(): void
    {
        $this->actingAs($this->user)
            ->deleteJson('/api/v1/profile', ['password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->actingAs($this->user)
            ->deleteJson('/api/v1/profile')
            ->assertStatus(422);

        $this->assertNull($this->user->fresh()->deleted_at);
    }

    public function test_google_account_without_password_is_deleted_without_one(): void
    {
        $google = User::factory()->create(['password' => null, 'google_id' => '123']);

        $this->actingAs($google)->getJson('/api/v1/user')->assertJsonPath('user.has_password', false);
        $this->actingAs($google)->deleteJson('/api/v1/profile')->assertOk();

        $this->assertNull($google->fresh()->google_id);
        $this->assertNotNull($google->fresh()->deleted_at);
    }

    public function test_order_in_progress_blocks_deletion(): void
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => Order::STATUS_PREPARING]);

        $this->actingAs($this->user)
            ->deleteJson('/api/v1/profile', ['password' => 'secret-pass'], ['X-Locale' => 'tk'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Sizde ýerine ýetirilýän sargyt bar. Hasaby ol tamamlanandan soň pozup bilersiňiz.');

        $this->assertNull($this->user->fresh()->deleted_at);
        $this->assertSame('Ага Агаев', $this->user->fresh()->name);
    }

    public function test_staff_cannot_delete_themselves_from_the_app(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR, 'password' => 'secret-pass']);

        $this->actingAs($owner)
            ->deleteJson('/api/v1/profile', ['password' => 'secret-pass'])
            ->assertStatus(403);

        $this->assertNull($owner->fresh()->deleted_at);
    }

    public function test_guest_cannot_delete(): void
    {
        $this->deleteJson('/api/v1/profile')->assertStatus(401);
    }
}
