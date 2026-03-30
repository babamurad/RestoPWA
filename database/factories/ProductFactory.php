<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Menu\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'vendor_id' => null,
            'category_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(100, 10000),
            'image' => null,
            'weight_g' => fake()->numberBetween(50, 1000),
            'modifiers' => [],
            'is_available' => true,
        ];
    }

    public function forVendor(string $vendorId): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendorId,
        ]);
    }

    public function forCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    public function withPrice(int $priceInCents): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $priceInCents,
        ]);
    }
}
