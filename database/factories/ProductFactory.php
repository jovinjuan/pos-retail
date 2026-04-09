<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => null,
            'name'        => fake()->unique()->words(3, true),
            'sku'         => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'sell_price'  => fake()->randomFloat(2, 1000, 500000),
            'cost_price'  => fake()->randomFloat(2, 500, 400000),
            'stock'       => fake()->numberBetween(10, 100),
            'min_stock'   => 5,
            'unit'        => 'pcs',
            'image_path'  => null,
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function belowMinStock(): static
    {
        return $this->state(['stock' => 2, 'min_stock' => 10]);
    }

    public function withCategory(): static
    {
        return $this->for(\App\Models\Category::factory());
    }
}
