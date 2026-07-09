<?php

namespace Tests\Browser;

use App\Models\User;
use App\Domains\Order\Models\Order;
use Database\Factories\RestaurantFactory;
use Database\Factories\ProductFactory;
use Database\Factories\CategoryFactory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;
use App\Enums\UserRole;

class ClientRoleTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $restaurant;
    protected $category;
    protected $product;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->restaurant = RestaurantFactory::new()->create(['slug' => 'test-vendor-' . uniqid(), 'is_active' => true]);
        
        $this->category = CategoryFactory::new()->create([
            'vendor_id' => $this->restaurant->id,
            'name' => 'Main Menu',
            'is_active' => true
        ]);

        $this->product = ProductFactory::new()->create([
            'vendor_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'is_available' => true,
            'price' => 500.00,
            'name' => 'Pizza Margherita'
        ]);

        $this->clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'password' => bcrypt('password')
        ]);
    }

    /**
     * CL-01: Client can browse, add to cart, and checkout
     */
    #[Group('client-role')]
    public function test_client_can_checkout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->clientUser)
                    ->visit('/restaurants/' . $this->restaurant->slug)
                    ->clearPwaCache()->refresh()
                    ->waitForText($this->product->name, 20);
            
            $browser->script("Object.defineProperty(navigator, 'onLine', { value: true }); if(window.setOfflineState) window.setOfflineState(false);");
            
            $browser->click('@add-to-cart-'.$this->product->id)
                    ->pause(1500)
                    
                    ->visit('/cart')
                    ->waitForText($this->product->name, 15)
                    ->click('@cart-checkout-button')
                    
                    // Шаг 1: Адрес
                    ->waitForText('Адрес доставки', 20)
                    ->pause(3000)
                    ->setAddress('ул. Ленина, д. 1', 55.0, 37.0)
                    ->pause(3000)
                    ->press('Продолжить')
                    
                    // Шаг 2: Время
                    ->waitForText('Время доставки', 15)
                    ->pause(1000)
                    ->press('Продолжить')
                    
                    // Шаг 3: Контакты (должны быть предзаполнены для авторизованного)
                    ->waitForText('Ваши контакты', 15)
                    ->assertInputValue('name', $this->clientUser->name)
                    // If phone is missing, add it
                    ->type('phone', '79998887766')
                    ->press('Продолжить')
                    
                    // Шаг 4: Проверка корзины
                    ->waitForText('Проверка корзины', 15)
                    ->pause(1000)
                    ->press('К оплате')
                    
                    // Шаг 5: Подтверждение
                    ->waitForText('Подтверждение заказа', 15)
                    ->click('@checkout-submit-button')
                    
                    // Шаг 6: Финальная страница
                    ->waitForText('Заказ', 30);
        });
    }
    
    /**
     * CL-02: Client can view order tracking
     */
    #[Group('client-role')]
    public function test_client_can_view_order_tracking(): void
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'user_id' => $this->clientUser->id,
            'status' => 'cooking',
            'address' => ['address' => 'Test Address'],
            'total' => 1000
        ]);

        $this->browse(function (Browser $browser) use ($order) {
            $browser->loginAs($this->clientUser)
                    ->visit('/order/' . $order->id . '/track')
                    ->waitForText('Статус заказа', 10)
                    ->assertSee('Готовится');
        });
    }
}
