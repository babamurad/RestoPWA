<?php

namespace Database\Seeders;

use App\Domains\Geo\Models\District;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\PaymentTransaction;
use App\Domains\Review\Models\Review;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Vendor\Models\RestaurantDeliveryPrice;
use App\Domains\Vendor\Services\TenantContext;
use App\Enums\OrderRejectReason;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Models\VendorSettlement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills the local database with realistic demo data so every admin screen
 * (platform analytics, restaurant analytics, orders, couriers, reviews,
 * rejections, settlements) has something to show. Safe to run only once:
 * guarded by a deterministic demo user so a second run does not double data.
 *
 * Run with: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private const DAYS_BACK = 70;

    /** @var list<array<string, mixed>> */
    private array $statusHistoryRows = [];

    public function run(): void
    {
        if (User::where('email', 'demo.client1@restopwa.local')->exists()) {
            $this->command?->warn('Demo data already present — skipping (delete demo.* users to reseed).');

            return;
        }

        $this->call(RestaurantSeeder::class);
        app(TenantContext::class)->setCurrentVendor(null);

        DB::transaction(function () {
            $restaurants = $this->configureRestaurants();
            $productsByRestaurant = $restaurants->mapWithKeys(
                fn (Restaurant $r) => [$r->id => Product::where('vendor_id', $r->id)->get()]
            );

            $this->seedDistricts($restaurants);

            $clients = $this->seedClients();
            $couriers = $this->seedCouriers($restaurants);

            $this->seedOrders($restaurants, $productsByRestaurant, $clients, $couriers);
            $this->seedRejections($restaurants, $clients);
            $this->seedSettlements($restaurants);
            $this->seedSmsLogs($clients);
            $this->seedSupportExtras($clients);

            $this->flushStatusHistory();
        });

        $this->command?->info('Demo data seeded: clients, couriers, ~'.self::DAYS_BACK.' days of orders, reviews, rejections, settlements.');
    }

    /** Give every seeded restaurant a commission so commission/payout figures are non-zero. */
    private function configureRestaurants(): Collection
    {
        $commission = [
            'pepperoni-pizza' => 15.0,
            'burger-house' => 12.0,
            'sakura-sushi' => 18.0,
            'green-salad' => 10.0,
            'sweet-dreams' => 15.0,
            'coffee-time' => 8.0,
        ];

        $restaurants = Restaurant::where('is_active', true)->whereIn('slug', array_keys($commission))->get();

        foreach ($restaurants as $restaurant) {
            $restaurant->forceFill([
                'commission_percent' => $commission[$restaurant->slug],
                'courier_fixed_fee' => 1000, // 10 TMT, minor units
                'courier_percent_fee' => 0,
            ])->save();
        }

        return $restaurants;
    }

    private function seedDistricts(Collection $restaurants): Collection
    {
        $names = ['Центр', 'Бахар', '3-й микрорайон', 'Сельхоз', 'Жилгородок'];

        $districts = collect($names)->map(fn (string $name, int $i) => District::firstOrCreate(
            ['name' => $name, 'city' => 'Туркменабат'],
            ['is_active' => true, 'sort_order' => $i]
        ))->values();

        foreach ($restaurants as $restaurant) {
            foreach ($districts as $district) {
                RestaurantDeliveryPrice::firstOrCreate(
                    ['restaurant_id' => $restaurant->id, 'district_id' => $district->id],
                    ['price' => fake()->randomElement([800, 1000, 1200, 1500, 1800, 2000])]
                );
            }
        }

        return $districts;
    }

    /** @return Collection<int, User> */
    private function seedClients(): Collection
    {
        $firstNames = ['Азат', 'Мердан', 'Сердар', 'Бегенч', 'Довлет', 'Ровшен', 'Кемал', 'Шохрат', 'Полат', 'Батыр',
            'Гульнара', 'Айна', 'Зохра', 'Майса', 'Огулджан', 'Джемал', 'Лейла', 'Марал', 'Дженнет', 'Амангуль'];
        $lastNames = ['Чарыев', 'Атаев', 'Ильясов', 'Гурбанов', 'Ниязов', 'Худайбердиев', 'Сапаров', 'Оразов', 'Бердиев', 'Мамедов'];

        return collect(range(1, 24))->map(function (int $i) use ($firstNames, $lastNames) {
            $name = $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)];
            $createdAt = Carbon::now()->subDays(random_int(0, self::DAYS_BACK))->subHours(random_int(0, 23));

            $user = new User;
            $user->fill([
                'name' => $name,
                'email' => 'demo.client'.$i.'@restopwa.local',
                'password' => 'password',
                'role' => UserRole::CLIENT,
                'phone' => '+993'.random_int(61000000, 65999999),
                'locale' => 'ru',
            ]);
            $this->touch($user, $createdAt);

            if ($i <= 5) {
                UserAddress::create([
                    'user_id' => $user->id,
                    'title' => 'Дом',
                    'full_address' => 'г. Туркменабат, ' . ['3-й микрорайон', 'Бахар', 'Центр'][array_rand(['3-й микрорайон', 'Бахар', 'Центр'])] . ', дом ' . random_int(1, 60),
                    'floor' => (string) random_int(1, 9),
                    'apartment' => (string) random_int(1, 120),
                    'lat' => 39.05 + mt_rand(-400, 400) / 10000,
                    'lng' => 63.58 + mt_rand(-400, 400) / 10000,
                    'is_default' => true,
                ]);
            }

            if ($i <= 3) {
                UserPaymentMethod::create([
                    'user_id' => $user->id,
                    'card_mask' => '**** **** **** '.random_int(1000, 9999),
                    'card_type' => fake()->randomElement(['Visa', 'MasterCard']),
                    'is_default' => true,
                ]);
            }

            return $user;
        });
    }

    /** @return Collection<int, Courier> */
    private function seedCouriers(Collection $restaurants): Collection
    {
        $specs = [
            ['name' => 'Ахмет Курбанов', 'vehicle' => 'car', 'status' => Courier::STATUS_AVAILABLE, 'bind' => ['sakura-sushi', 'burger-house']],
            ['name' => 'Батыр Оразов', 'vehicle' => 'bike', 'status' => Courier::STATUS_AVAILABLE, 'bind' => []],
            ['name' => 'Вепа Ниязов', 'vehicle' => 'bike', 'status' => Courier::STATUS_BUSY, 'bind' => []],
            ['name' => 'Гурбан Сапаров', 'vehicle' => 'walking', 'status' => Courier::STATUS_AVAILABLE, 'bind' => ['coffee-time']],
            ['name' => 'Дангатар Атаев', 'vehicle' => 'car', 'status' => Courier::STATUS_OFFLINE, 'bind' => []],
            ['name' => 'Ешмурад Бердиев', 'vehicle' => 'bike', 'status' => Courier::STATUS_OFFLINE, 'bind' => []],
        ];

        return collect($specs)->map(function (array $spec, int $i) use ($restaurants) {
            $user = new User;
            $user->fill([
                'name' => $spec['name'],
                'email' => 'demo.courier'.$i.'@restopwa.local',
                'password' => 'password',
                'role' => UserRole::COURIER,
                'phone' => '+993'.random_int(62000000, 65999999),
                'locale' => 'ru',
            ]);
            $this->touch($user, Carbon::now()->subDays(self::DAYS_BACK));

            $courier = Courier::create([
                'user_id' => $user->id,
                'vehicle_type' => $spec['vehicle'],
                'status' => $spec['status'],
                'current_lat' => 39.05 + mt_rand(-500, 500) / 10000,
                'current_lon' => 63.58 + mt_rand(-500, 500) / 10000,
                'last_assigned_at' => now()->subMinutes(random_int(5, 600)),
            ]);

            foreach ($spec['bind'] as $slug) {
                $restaurant = $restaurants->firstWhere('slug', $slug);
                if ($restaurant) {
                    $courier->restaurants()->syncWithoutDetaching([$restaurant->id]);
                }
            }

            return $courier;
        });
    }

    private function seedOrders(Collection $restaurants, Collection $productsByRestaurant, Collection $clients, Collection $couriers): void
    {
        $weights = ['sakura-sushi' => 5, 'burger-house' => 4, 'pepperoni-pizza' => 4, 'green-salad' => 2, 'sweet-dreams' => 2, 'coffee-time' => 2];

        $pool = [];
        foreach ($restaurants as $restaurant) {
            for ($w = 0, $max = $weights[$restaurant->slug] ?? 1; $w < $max; $w++) {
                $pool[] = $restaurant;
            }
        }

        for ($dayOffset = self::DAYS_BACK; $dayOffset >= 0; $dayOffset--) {
            $day = Carbon::now()->subDays($dayOffset)->startOfDay();
            $isRecent = $dayOffset <= 1;
            $weekendBoost = in_array($day->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true) ? 3 : 0;
            $ordersCount = $isRecent ? random_int(10, 16) : random_int(4, 12) + $weekendBoost;

            for ($n = 0; $n < $ordersCount; $n++) {
                /** @var Restaurant $restaurant */
                $restaurant = $pool[array_rand($pool)];
                $products = $productsByRestaurant[$restaurant->id] ?? collect();
                if ($products->isEmpty()) {
                    continue;
                }

                $client = $clients->random();
                $deliveryType = random_int(1, 100) <= 78 ? Order::DELIVERY_TYPE_DELIVERY : Order::DELIVERY_TYPE_PICKUP;

                $createdAt = $day->copy()->addHours(random_int(9, 22))->addMinutes(random_int(0, 59));
                if ($createdAt->greaterThan(now())) {
                    $createdAt = now()->copy()->subMinutes(random_int(1, 180));
                }

                $statusPath = $this->buildStatusPath($deliveryType, $isRecent);

                $this->createDemoOrder($restaurant, $products, $client, $deliveryType, $createdAt, $statusPath, $couriers);
            }
        }
    }

    /** @return list<string> ordered statuses; the last one is the order's current status */
    private function buildStatusPath(string $deliveryType, bool $allowLive): array
    {
        $base = $deliveryType === Order::DELIVERY_TYPE_PICKUP
            ? [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_COMPLETED]
            : [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED];

        $roll = random_int(1, 100);

        if ($roll <= 10) {
            $cutoff = random_int(1, count($base) - 1);

            return array_merge(array_slice($base, 0, $cutoff), [Order::STATUS_CANCELLED]);
        }

        if ($allowLive && $roll <= 45) {
            $cutoff = random_int(1, count($base) - 1);

            return array_slice($base, 0, $cutoff);
        }

        return $base;
    }

    private function createDemoOrder(
        Restaurant $restaurant,
        Collection $products,
        User $client,
        string $deliveryType,
        Carbon $createdAt,
        array $statusPath,
        Collection $couriers
    ): void {
        $chosen = $products->random(min(random_int(1, 4), $products->count()));

        $items = [];
        $itemsTotalCents = 0;

        foreach ($chosen as $product) {
            $quantity = random_int(1, 3);
            $unitPriceCents = (int) round(((float) $product->price) * 100);
            $totalPriceCents = $unitPriceCents * $quantity;
            $itemsTotalCents += $totalPriceCents;

            $items[] = [
                'name' => $product->name,
                'image' => $product->image,
                'quantity' => $quantity,
                'modifiers' => [],
                'product_id' => $product->id,
                'unit_price' => $unitPriceCents,
                'total_price' => $totalPriceCents,
            ];
        }

        $isPickup = $deliveryType === Order::DELIVERY_TYPE_PICKUP;
        $deliveryFeeCents = $isPickup ? 0 : (int) round((((float) $restaurant->delivery_fee) ?: 15.0) * 100);
        $totalCents = $itemsTotalCents + $deliveryFeeCents;

        $finalStatus = (string) end($statusPath);
        $isCancelled = $finalStatus === Order::STATUS_CANCELLED;
        $isFinished = in_array($finalStatus, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true);

        $paymentMethod = random_int(1, 100) <= 30 ? 'card' : 'cash';

        $commissionCents = $isFinished
            ? (int) round($itemsTotalCents * ((float) $restaurant->commission_percent) / 100)
            : 0;

        $paymentStatus = PaymentStatus::UNPAID;
        if ($paymentMethod === 'card') {
            $paymentStatus = match (true) {
                $isCancelled => random_int(1, 100) <= 50 ? PaymentStatus::REFUNDED : PaymentStatus::PAYMENT_FAILED,
                $isFinished => PaymentStatus::PAID,
                default => PaymentStatus::AWAITING_PAYMENT,
            };
        }

        $courier = (! $isPickup && in_array($finalStatus, [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED], true))
            ? $couriers->random()
            : null;

        $timestamps = [];
        $cursor = $createdAt->copy();
        foreach ($statusPath as $status) {
            $timestamps[] = $cursor->copy();
            $cursor = $cursor->copy()->addMinutes(random_int(3, 20));
        }
        $updatedAt = end($timestamps) ?: $createdAt;

        $order = new Order;
        $order->fill([
            'vendor_id' => $restaurant->id,
            'user_id' => $client->id,
            'courier_id' => $courier?->id,
            'status' => $finalStatus,
            'delivery_type' => $deliveryType,
            'payment_status' => $paymentStatus,
            'address' => $this->fakeAddress($client),
            'items' => $items,
            'total' => $totalCents,
            'delivery_fee' => $deliveryFeeCents,
            'commission_amount' => $commissionCents,
            'delivery_time' => 'asap',
            'payment_method' => $paymentMethod,
            'is_offline' => false,
            'metadata' => ['created_via' => 'demo_seed'],
        ]);
        $this->touch($order, $createdAt, $updatedAt);

        $from = null;
        foreach ($statusPath as $i => $status) {
            $this->statusHistoryRows[] = [
                'id' => (string) Str::orderedUuid(),
                'order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $status,
                'metadata' => json_encode(['created_via' => 'demo_seed']),
                'created_at' => $timestamps[$i],
            ];
            $from = $status;
        }

        if ($paymentMethod === 'card' && in_array($paymentStatus, [PaymentStatus::PAID, PaymentStatus::REFUNDED, PaymentStatus::PAYMENT_FAILED], true)) {
            $transaction = new PaymentTransaction;
            $transaction->fill([
                'order_id' => $order->id,
                'provider' => 'card',
                'type' => $paymentStatus === PaymentStatus::REFUNDED ? PaymentTransaction::TYPE_REFUND : PaymentTransaction::TYPE_PAYMENT,
                'status' => $paymentStatus === PaymentStatus::PAYMENT_FAILED ? PaymentTransaction::STATUS_FAILED : PaymentTransaction::STATUS_SUCCEEDED,
                'amount' => $totalCents,
                'currency' => 'TMT',
                'idempotency_key' => 'demo:'.$order->id.':'.$paymentStatus->value,
            ]);
            $this->touch($transaction, $updatedAt);
        }

        if ($courier && $isFinished) {
            $earning = new CourierEarning;
            $earning->fill([
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'amount' => round($deliveryFeeCents / 100, 2),
                'status' => $createdAt->lt(now()->subDay()) ? 'paid' : 'pending',
            ]);
            $this->touch($earning, $updatedAt);
        }

        if ($isFinished && random_int(1, 100) <= 55) {
            $this->createReview($order, $client, $restaurant, $updatedAt);
        }
    }

    private function fakeAddress(User $client): array
    {
        return [
            'lat' => 39.05 + mt_rand(-400, 400) / 10000,
            'lon' => 63.58 + mt_rand(-400, 400) / 10000,
            'name' => $client->name,
            'floor' => (string) random_int(1, 9),
            'phone' => $client->phone,
            'address' => 'Точка на карте',
            'entrance' => (string) random_int(1, 4),
            'landmark' => null,
            'apartment' => (string) random_int(1, 120),
            'manual_address' => 'Точка на карте',
            'courier_comment' => null,
            'address_source' => 'map_pin',
        ];
    }

    private function createReview(Order $order, User $client, Restaurant $restaurant, Carbon $at): void
    {
        $rating = $this->weightedRating();
        $comments = [
            5 => ['Очень вкусно и быстро!', 'Всё супер, доставка вовремя.', 'Отличный сервис, буду заказывать ещё.'],
            4 => ['Хорошо, но ехали чуть дольше обычного.', 'Вкусно, упаковка аккуратная.'],
            3 => ['Нормально, но могло быть горячее.', 'Среднее впечатление.'],
            2 => ['Доставили холодным.', 'Долго ждал заказ.'],
            1 => ['Заказ приехал неполный.', 'Очень долго и остыло.'],
        ];

        $review = new Review;
        $review->fill([
            'order_id' => $order->id,
            'user_id' => $client->id,
            'restaurant_id' => $restaurant->id,
            'rating' => $rating,
            'comment' => $comments[$rating][array_rand($comments[$rating])],
            'is_hidden' => false,
        ]);

        if (random_int(1, 100) <= 30) {
            $review->vendor_reply = 'Спасибо за отзыв! Будем рады видеть вас снова.';
            $review->replied_at = $at->copy()->addHours(random_int(1, 20));
        }

        $this->touch($review, $at);
    }

    private function weightedRating(): int
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 45 => 5,
            $roll <= 75 => 4,
            $roll <= 90 => 3,
            $roll <= 97 => 2,
            default => 1,
        };
    }

    private function seedRejections(Collection $restaurants, Collection $clients): void
    {
        $reasons = [
            OrderRejectReason::OUTSIDE_DELIVERY_ZONE,
            OrderRejectReason::BELOW_MIN_ORDER,
            OrderRejectReason::RESTAURANT_CLOSED,
            OrderRejectReason::ITEM_UNAVAILABLE,
            OrderRejectReason::INVALID_PHONE,
            OrderRejectReason::VALIDATION,
            OrderRejectReason::NETWORK,
            OrderRejectReason::ZONE_NOT_CONFIGURED,
        ];

        $rows = [];
        for ($dayOffset = self::DAYS_BACK; $dayOffset >= 0; $dayOffset--) {
            if (random_int(1, 100) > 55) {
                continue;
            }

            $day = Carbon::now()->subDays($dayOffset);

            for ($i = 0, $count = random_int(1, 4); $i < $count; $i++) {
                $restaurant = random_int(1, 100) <= 85 ? $restaurants->random() : null;
                $client = random_int(1, 100) <= 60 ? $clients->random() : null;

                $rows[] = [
                    'reason' => $reasons[array_rand($reasons)]->value,
                    'vendor_id' => $restaurant?->id,
                    'user_id' => $client?->id,
                    'created_at' => $day->copy()->addHours(random_int(8, 23))->addMinutes(random_int(0, 59)),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('order_rejections')->insert($chunk);
        }
    }

    private function seedSettlements(Collection $restaurants): void
    {
        foreach ($restaurants as $restaurant) {
            for ($weeksAgo = 3; $weeksAgo >= 1; $weeksAgo--) {
                $from = Carbon::now()->subWeeks($weeksAgo)->startOfWeek();
                $to = $from->copy()->endOfWeek();

                $finished = Order::where('vendor_id', $restaurant->id)
                    ->whereBetween('created_at', [$from, $to])
                    ->get()
                    ->filter(fn (Order $o) => $o->isFinished());

                if ($finished->isEmpty()) {
                    continue;
                }

                $gross = (int) round($finished->sum('total') * 100);
                $commission = (int) round($finished->sum('commission_amount') * 100);
                $deliveryFees = (int) round($finished->sum('delivery_fee') * 100);

                $settlement = new VendorSettlement;
                $settlement->fill([
                    'restaurant_id' => $restaurant->id,
                    'period_from' => $from->toDateString(),
                    'period_to' => $to->toDateString(),
                    'gross_amount' => $gross,
                    'commission_amount' => $commission,
                    'net_payable' => max(0, $gross - $deliveryFees - $commission),
                    'status' => $weeksAgo === 1 ? 'approved' : 'paid',
                    'paid_at' => $weeksAgo === 1 ? null : $to->copy()->addDays(2),
                ]);
                $settlement->save();
            }
        }
    }

    private function seedSmsLogs(Collection $clients): void
    {
        $messages = [
            'Ваш код подтверждения: '.random_int(1000, 9999),
            'Заказ №1032 передан курьеру',
            'Ваш заказ доставлен, спасибо за покупку!',
        ];

        $rows = [];
        foreach (range(1, 18) as $i) {
            $client = $clients->random();
            $success = random_int(1, 100) <= 85;
            $createdAt = now()->subDays(random_int(0, self::DAYS_BACK))->subHours(random_int(0, 23));

            $rows[] = [
                'id' => (string) Str::orderedUuid(),
                'phone' => $client->phone,
                'message' => $messages[array_rand($messages)],
                'status' => $success ? 'sent' : 'failed',
                'provider' => 'nexmo',
                'error_message' => $success ? null : 'Таймаут провайдера',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DB::table('sms_logs')->insert($rows);
    }

    private function seedSupportExtras(Collection $clients): void
    {
        $tickets = [
            ['Не пришёл заказ', 'Заказ №1032 так и не привезли, курьер не отвечает.'],
            ['Ошибка оплаты', 'Деньги списались, но заказ не оформился, помогите разобраться.'],
            ['Вопрос по доставке', 'Можно ли доставить в соседний район, за пределами зоны?'],
        ];

        foreach ($clients->take(3) as $i => $client) {
            [$subject, $message] = $tickets[$i];

            $ticket = new SupportTicket;
            $ticket->fill([
                'user_id' => $client->id,
                'subject' => $subject,
                'message' => $message,
                'status' => $i === 0 ? 'open' : 'closed',
            ]);
            $this->touch($ticket, now()->subDays(random_int(1, self::DAYS_BACK)));
        }
    }

    private function touch(Model $model, Carbon $createdAt, ?Carbon $updatedAt = null): void
    {
        $model->created_at = $createdAt;
        $model->updated_at = $updatedAt ?? $createdAt;
        $model->timestamps = false;
        $model->save();
    }

    private function flushStatusHistory(): void
    {
        foreach (array_chunk($this->statusHistoryRows, 300) as $chunk) {
            DB::table('order_status_history')->insert($chunk);
        }
    }
}
