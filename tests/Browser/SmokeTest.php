<?php

namespace Tests\Browser;

use App\Models\User;
use App\Domains\Order\Models\Order;
use Database\Factories\RestaurantFactory;
use Database\Factories\ProductFactory;
use Database\Factories\CategoryFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;

class SmokeTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $restaurant;
    protected $category;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->restaurant = RestaurantFactory::new()->create(['slug' => 'test-vendor', 'is_active' => true]);
        
        $this->category = CategoryFactory::new()->create([
            'vendor_id' => $this->restaurant->id,
            'name' => 'Main Menu',
            'is_active' => true
        ]);

        $this->product = ProductFactory::new()->create([
            'vendor_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'is_available' => true,
            'price' => 500,
            'name' => 'Pizza Margherita'
        ]);
    }

    /**
     * SMK-01: Guest happy path checkout
     */
    #[Group('smoke')]
    #[Group('merge-gate')]
    public function test_guest_happy_path_checkout(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/restaurants/' . $this->restaurant->slug)
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
                    ->setAddress('ул. Пушкина, д. 10', 45.0, 45.0)
                    ->pause(3000)
                    ->press('Продолжить')
                    
                    // Шаг 2: Время
                    ->waitForText('Время доставки', 15)
                    ->pause(1000)
                    ->press('Продолжить')
                    
                    // Шаг 3: Контакты
                    ->waitForText('Ваши контакты', 15)
                    ->type('name', 'Test Guest')
                    ->type('phone', '79998887766')
                    ->press('Продолжить')
                    
                    // Шаг 4: Проверка корзины
                    ->waitForText('Проверка корзины', 15)
                    ->pause(1000)
                    ->press('К оплате')
                    
                    // Шаг 5: Подтверждение
                    ->waitForText('Подтверждение заказа', 15)
                    ->click('@checkout-submit-button')
                    
                    // Успех
                    ->waitForText('Заказ оформлен!', 25)
                    ->assertSee('Ваш заказ #');
        });
    }
    
    /**
     * SMK-02: Order status visibility (via direct tracking link)
     */
    #[Group('smoke')]
    public function test_order_status_visibility(): void
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->restaurant->id,
            'status' => 'cooking',
            'address' => ['address' => 'Test Address'],
            'total' => 1000
        ]);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'order.track.guest',
            now()->addHours(24),
            ['orderId' => $order->id]
        );

        $this->browse(function (Browser $browser) use ($url) {
            $browser->visit($url)
                    ->waitForText('Статус заказа', 10)
                    ->assertSee('Готовится');
        });
    }

    /**
     * SMK-05: Guest tracking security (tamper/invalid signature)
     */
    #[Group('smoke')]
    public function test_guest_tracking_security(): void
    {
        $order = Order::factory()->create(['vendor_id' => $this->restaurant->id]);

        $this->browse(function (Browser $browser) use ($order) {
            // Без подписи
            $browser->visit('/order/' . $order->id . '/track/guest')
                    ->assertSee('403');
            
            // С невалидной подписью
            $browser->visit('/order/' . $order->id . '/track/guest?signature=invalid')
                    ->assertSee('403');
        });
    }

    /**
     * SMK-03: Пустая корзина
     */
    #[Group('smoke')]
    public function test_empty_cart_redirect(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/cart')
                    ->clearPwaCache()->refresh()
                    ->waitUntilMissingText('Loading...', 10)
                    ->waitForText('Корзина пуста', 15);
        });
    }
}

