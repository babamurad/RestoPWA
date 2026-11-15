<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\NewOrderForRestaurant;
use App\Notifications\OrderReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewOrderEmailTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(Restaurant $restaurant, User $customer): Order
    {
        return app(OrderService::class)->createOrder([
            'vendor_id' => $restaurant->id,
            'user_id' => $customer->id,
            'items' => [['product_id' => 'p1', 'name' => 'Плов', 'quantity' => 2, 'unit_price' => 5000, 'total_price' => 10000]],
            'total' => 10500,
            'delivery_fee' => 500,
            'payment_method' => 'cash',
            'address' => ['address' => 'ул. Битарап 10', 'lat' => 39.09, 'lon' => 63.56],
        ]);
    }

    public function test_new_order_emails_the_owner_and_a_receipt_to_the_customer(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $customer = User::factory()->create();

        $order = $this->createOrder($restaurant, $customer);

        Notification::assertSentTo($owner, NewOrderForRestaurant::class, fn ($n) => $n->order->is($order));
        Notification::assertSentTo($customer, OrderReceipt::class, fn ($n) => $n->order->is($order));
    }

    public function test_mail_trouble_does_not_fail_the_order(): void
    {
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP is down'));
        $restaurant = Restaurant::factory()->create();

        $order = $this->createOrder($restaurant, User::factory()->create());

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_receipt_is_written_in_the_customers_language(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Sakura']);

        foreach (['ru', 'tk'] as $locale) {
            $customer = User::factory()->create(['locale' => $locale]);
            $order = Order::factory()->create([
                'vendor_id' => $restaurant->id,
                'user_id' => $customer->id,
                'items' => [['name' => 'Плов', 'quantity' => 2, 'total_price' => 10000]],
                'total' => 105.0,
                'delivery_fee' => 5.0,
            ]);

            $mail = (new OrderReceipt($order))->toMail($customer);
            $text = implode("\n", [$mail->subject, $mail->greeting, ...$mail->introLines, ...$mail->outroLines]);

            $this->assertSame(__('notifications.receipt.subject', ['number' => $order->order_number], $locale), $mail->subject);
            $this->assertStringContainsString('Плов × 2 — 100,00 TMT', $text);
            $this->assertStringContainsString(__('notifications.receipt.total', ['amount' => '105,00 TMT'], $locale), $text);
            $this->assertStringNotContainsString('notifications.', $text);
        }
    }

    public function test_owner_email_lists_the_order(): void
    {
        $order = Order::factory()->create([
            'items' => [['name' => 'Плов', 'quantity' => 2, 'total_price' => 10000]],
            'total' => 105.0,
            'comment' => 'Без лука',
        ]);

        $mail = (new NewOrderForRestaurant($order))->toMail(User::factory()->make());
        $text = implode("\n", [$mail->subject, ...$mail->introLines, ...$mail->outroLines]);

        $this->assertStringContainsString((string) $order->order_number, $mail->subject);
        $this->assertStringContainsString('Плов × 2', $text);
        $this->assertStringContainsString('Без лука', $text);
    }
}
