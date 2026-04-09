<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 1000, 100000);
        $quantity  = fake()->numberBetween(1, 10);

        return [
            'transaction_id' => Transaction::factory(),
            'product_id'     => Product::factory(),
            'product_name'   => fake()->words(3, true),
            'unit_price'     => $unitPrice,
            'quantity'       => $quantity,
            'subtotal'       => $unitPrice * $quantity,
        ];
    }
}
