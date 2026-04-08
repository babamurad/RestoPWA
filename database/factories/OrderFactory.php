<?php

namespace Database\Factories;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Restaurant::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'address' => [
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
                'house' => fake()->buildingNumber(),
                'apartment' => fake()->randomDigitNotNull(),
            ],
            'items' => [
                [
                    'product_id' => fake()->uuid(),
                    'product_name' => fake()->word(),
                    'quantity' => fake()->numberBetween(1, 5),
                    'unit_price' => 10.0,
                    'total_price' => 10.0,
                ],
            ],
            'total' => 10.0,
            'delivery_fee' => 0.0,
            'payment_method' => 'card',
            'is_offline' => false,
        ];
    }
}
