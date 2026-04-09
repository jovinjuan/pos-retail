<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        $stockBefore = fake()->numberBetween(0, 100);
        $change      = fake()->numberBetween(-$stockBefore, 50);

        return [
            'product_id'      => Product::factory(),
            'user_id'         => User::factory(),
            'quantity_change' => $change,
            'stock_before'    => $stockBefore,
            'stock_after'     => $stockBefore + $change,
            'reason'          => fake()->sentence(),
        ];
    }

    public function addition(): static
    {
        return $this->state(fn () => [
            'quantity_change' => fake()->numberBetween(1, 50),
        ]);
    }

    public function reduction(int $currentStock = 10): static
    {
        $change = -fake()->numberBetween(1, $currentStock);
        return $this->state(fn () => [
            'stock_before'    => $currentStock,
            'quantity_change' => $change,
            'stock_after'     => $currentStock + $change,
        ]);
    }
}
