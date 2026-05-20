<?php

namespace Database\Factories;

use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Str::uuid()->toString(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->paragraph(),
            'settings' => [
                'timezone' => 'Europe/Moscow',
                'currency' => 'RUB',
            ],
            'is_active' => true,
            'commission_rate' => fake()->randomFloat(2, 5, 20),
            'owner_id' => UserFactory::new(),
            'delivery_zones' => '{"type":"MultiPolygon","coordinates":[[[[63.0,39.0],[64.0,39.0],[64.0,40.0],[63.0,40.0],[63.0,39.0]]]]}',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forVendor(string $vendorId): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendorId,
        ]);
    }
}
