<?php

declare(strict_types=1);

namespace Tests\Browser\Concerns;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Models\User;

/** Test data for the browser tests: restaurants with a menu, and people with a password. */
trait BuildsRestaurants
{
    public const PASSWORD = 'password123';

    /** @param  array<string, mixed>  $attributes */
    protected function makeRestaurant(string $name, string $slug, array $attributes = []): Restaurant
    {
        return Restaurant::factory()->create($attributes + [
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'min_order' => 0,
            'delivery_fee' => 0,
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    protected function makeDish(Restaurant $restaurant, string $name, float $price, array $attributes = []): Product
    {
        $category = Category::where('vendor_id', $restaurant->id)->first()
            ?? Category::factory()->create(['vendor_id' => $restaurant->id, 'name' => 'Меню', 'is_active' => true]);

        return Product::factory()->create($attributes + [
            'vendor_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'is_available' => true,
            'modifiers' => [],
        ]);
    }

    protected function makePerson(UserRole $role, string $name, string $email, array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'role' => $role,
            'name' => $name,
            'email' => $email,
            'password' => self::PASSWORD,
            'phone' => '+99361'.random_int(100000, 999999),
        ]);
    }

    protected function makeCourier(string $email = 'courier@test.local'): Courier
    {
        $user = $this->makePerson(UserRole::COURIER, 'Курьер Мерген', $email);

        return Courier::create(['user_id' => $user->id]);
    }

    /** @param  array<string, mixed>  $attributes */
    protected function makeOrder(Restaurant $restaurant, User $customer, string $status, array $attributes = []): Order
    {
        return Order::factory()->create($attributes + [
            'vendor_id' => $restaurant->id,
            'user_id' => $customer->id,
            'status' => $status,
            'delivery_type' => Order::DELIVERY_TYPE_DELIVERY,
            'payment_method' => 'cash',
            'address' => ['address' => 'ул. Битарап 10', 'lat' => 39.09, 'lon' => 63.56, 'phone' => $customer->phone],
            'total' => 60.0,
            'delivery_fee' => 10.0,
        ]);
    }

    /** The pizza of the ordering tests: a required size, optional toppings (at most two) and a counter. */
    protected function makePizza(Restaurant $restaurant, float $price = 100.0): Product
    {
        return $this->makeDish($restaurant, 'Пепперони', $price, [
            'modifiers' => [
                ['name' => 'Размер', 'type' => 'radio', 'required' => true, 'options' => [
                    ['name' => 'Маленькая', 'price' => 0],
                    ['name' => 'Большая', 'price' => 50],
                ]],
                ['name' => 'Топпинг', 'type' => 'checkbox', 'max' => 2, 'options' => [
                    ['name' => 'Сыр', 'price' => 30],
                    ['name' => 'Грибы', 'price' => 25],
                    ['name' => 'Бекон', 'price' => 40],
                ]],
            ],
        ]);
    }
}
