<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Menu\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'vendor_id' => null,
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function forVendor(string $vendorId): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendorId,
        ]);
    }

    public function forParent(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
