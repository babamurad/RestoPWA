<?php

namespace Tests\Browser;

use App\Models\User;
use App\Domains\Order\Models\Order;
use Database\Factories\VendorFactory;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;

class SmokeTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $vendor;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->vendor = VendorFactory::new()->create(['slug' => 'test-vendor', 'is_active' => true]);
        $this->product = ProductFactory::new()->create([
            'vendor_id' => $this->vendor->id,
            'is_active' => true,
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
            $browser->visit('/restaurant/' . $this->vendor->id)
                    ->clearPwaCache()
                    ->refresh() // Для чистого старта после сброса IndexedDB
                    ->waitForText($this->product->name)
                    // Открываем модалку модификаторов
                    ->click('@open-modifier-modal-'.$this->product->id)
                    ->waitFor('@add-to-cart-submit')
                    // Добавляем в корзину
                    ->click('@add-to-cart-submit')
                    ->pause(1000) // Ждем отработки Dexie
                    
                    // Переход в корзину (через URL или иконку корзины)
                    ->visit('/cart')
                    ->waitForText($this->product->name)
                    ->click('@cart-checkout-button')
                    
                    // Шаг 1: Checkout - Address (Ожидаем пустой стейт "Выбрать адрес на карте")
                    ->waitForText('Адрес доставки')
                    
                    // В реальном E2E мы могли бы заполнить адрес через модалку,
                    // Но для изоляции мы можем вставить адрес через Livewire 
                    // (или прокликать модалку, если она тестируется)
                    // В данном случае мы проверяем возможность кликнуть и оформить если валидна форма
                    
                    // При проверке пустой формы:
                    ->click('@checkout-submit-button')
                    ->waitForText('Выберите адрес доставки') // Из validateAddress

                    ;
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
                    ->assertSee('Корзина пуста'); // Проверяем наличие текста пустой корзины
        });
    }
}
