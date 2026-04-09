<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * test_guest_cannot_access_order_tracking — неавторизованный пользователь получает redirect на /login
     */
    public function test_guest_cannot_access_order_tracking(): void
    {
        $order = Order::factory()->create();

        $response = $this->get("/order/{$order->id}/track");

        $response->assertRedirect('/login');
    }

    /**
     * test_authenticated_user_cannot_access_another_users_order — авторизованный пользователь получает 403/404 при попытке открыть чужой заказ
     */
    public function test_authenticated_user_cannot_access_another_users_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get("/order/{$order->id}/track");

        // OrderTrackingController uses findOrFail within a where('user_id', Auth::id()) clause, results in 404
        $response->assertStatus(404);
    }

    /**
     * test_authenticated_user_can_access_own_order_tracking — владелец заказа успешно открывает трекинг
     */
    public function test_authenticated_user_can_access_own_order_tracking(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/order/{$order->id}/track");

        $response->assertStatus(200);
    }

    /**
     * test_guest_cannot_access_vendor_panel — неавторизованный получает redirect при доступе к /vendor/orders
     */
    public function test_guest_cannot_access_vendor_panel(): void
    {
        $response = $this->get('/vendor/orders');

        $response->assertRedirect('/login');
    }

    /**
     * test_vendor_cannot_see_other_vendors_orders — vendor видит только свои заказы
     */
    public function test_vendor_cannot_see_other_vendors_orders(): void
    {
        // Создаем два ресторана (вендора)
        $vendor1 = Restaurant::factory()->create(['vendor_id' => 'vendor-1']);
        $vendor2 = Restaurant::factory()->create(['vendor_id' => 'vendor-2']);
        
        // Создаем пользователя для доступа к панели
        $user = User::factory()->create();

        // Создаем заказы для каждого вендора
        $order1 = Order::factory()->create([
            'vendor_id' => $vendor1->id,
            'user_id' => $user->id,
        ]);
        
        $order2 = Order::factory()->create([
            'vendor_id' => $vendor2->id,
            'user_id' => $user->id,
        ]);

        // Имитируем доступ через Vendor 1
        // Используем X-Vendor-ID заголовок для установки контекста
        $response = $this->actingAs($user)
            ->withHeaders(['X-Vendor-ID' => 'vendor-1'])
            ->get('/vendor/orders');

        $response->assertStatus(200);
        
        // Проверяем, что видим только заказ первого вендора
        // Обратите внимание: мы ищем номер заказа или ID в контенте страницы
        $response->assertSee($order1->id);
        $response->assertDontSee($order2->id);
    }
}
