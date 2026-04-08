<?php

namespace App\Console\Commands;

use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Console\Command;

class TestSalesScenario extends Command
{
    protected $signature = 'test:sales {--clean : Clean test data before running}';

    protected $description = 'Run sales test scenario: add product -> create order -> change status -> verify';

    public function handle(): int
    {
        $this->info('=== Тест сценария продаж ===');

        if ($this->option('clean')) {
            $this->info('Очистка тестовых данных...');
            Order::where('comment', 'like', '%[TEST_ORDER]%')->delete();
            Product::where('name', 'like', '%[ТЕСТ]%')->delete();
        }

        $restaurant = Restaurant::first();
        if (! $restaurant) {
            $this->error('Ресторан не найден. Запустите сидер.');

            return Command::FAILURE;
        }
        $this->info("✓ Ресторан: {$restaurant->name}");

        $testProduct = Product::firstOrCreate(
            [
                'name' => 'Тестовый товар [ТЕСТ]',
                'vendor_id' => $restaurant->id,
            ],
            [
                'description' => 'Автоматически созданный тестовый товар для проверки сценария продаж',
                'price' => 999.99,
                'is_available' => true,
            ]
        );
        $this->info("✓ Товар: {$testProduct->name} (ID: {$testProduct->id})");

        $customer = User::firstOrCreate(
            ['email' => 'customer@test.local'],
            ['name' => 'Тестовый Клиент', 'password' => bcrypt('password')]
        );
        $this->info("✓ Клиент: {$customer->name}");

        $order = Order::create([
            'vendor_id' => $restaurant->id,
            'user_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'payment_status' => 'pending',
            'address' => [
                'name' => $customer->name,
                'phone' => '+7 999 123-45-67',
                'address' => 'ул. Тестовая, д. 1',
            ],
            'items' => [
                [
                    'product_id' => $testProduct->id,
                    'name' => $testProduct->name,
                    'price' => $testProduct->price,
                    'quantity' => 2,
                ],
            ],
            'total' => $testProduct->price * 2,
            'delivery_fee' => 100,
            'comment' => 'Тестовый заказ [TEST_ORDER]',
        ]);
        $this->info("✓ Заказ создан: {$order->id}");
        $this->info("  Статус: {$order->status_label}");
        $this->info("  Сумма: {$order->total} руб.");

        $this->info("\n--- Симуляция изменения статуса ---");

        foreach ($order->getNextStatuses() as $nextStatus) {
            $order->update(['status' => $nextStatus]);
            $this->info("  → Новый статус: {$order->fresh()->status_label}");

            if (count($order->getNextStatuses()) === 0) {
                break;
            }
        }

        $this->info("\n--- Проверка логирования ---");
        $history = $order->statusHistory()->get();
        $this->info('Записей в истории: '.$history->count());

        foreach ($history as $h) {
            $from = $h->from_status ? Order::STATUSES[$h->from_status]['label'] : 'начало';
            $to = Order::STATUSES[$h->to_status]['label'];
            $this->info("  {$from} → {$to}");
        }

        $this->info("\n=== Тест завершён ===");
        $this->info("Заказ: /admin/orders/{$order->id}");

        return Command::SUCCESS;
    }
}
