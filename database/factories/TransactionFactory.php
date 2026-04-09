<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 500000);
        $total    = $subtotal;

        return [
            'user_id'        => User::factory(),
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'subtotal'       => $subtotal,
            'discount'       => 0,
            'tax'            => 0,
            'total'          => $total,
            'payment_method' => PaymentMethod::Cash,
            'amount_paid'    => $total,
            'change_amount'  => 0,
            'status'         => TransactionStatus::Completed,
            'cancel_reason'  => null,
        ];
    }
}
