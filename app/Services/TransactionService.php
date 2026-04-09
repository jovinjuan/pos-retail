<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionService
{


    public function calculateItemSubtotal(float $price, int $qty): float
    {
        return $price * $qty;
    }

    public function calculateTotal(float $subtotal, float $discount, float $tax): float
    {
        return $subtotal - $discount + $tax;
    }

    public function calculateChange(float $amountPaid, float $total): float
    {
        return $amountPaid - $total;
    }


    public function generateInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $today  = Carbon::today()->format('Ymd');
            $prefix = "INV-{$today}-";

            $last = Transaction::where('invoice_number', 'like', $prefix . '%')
                ->orderByDesc('invoice_number')
                ->lockForUpdate()
                ->value('invoice_number');

            $sequence = $last
                ? (int) substr($last, -4) + 1
                : 1;

            return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        });
    }


    /**
     * Create a completed transaction from cart data.
     *
     * Expected $data shape:
     * [
     *   'items'          => [['product_id' => int, 'quantity' => int], ...],
     *   'payment_method' => string,
     *   'amount_paid'    => float,
     *   'discount'       => float (optional, default 0),
     *   'tax'            => float (optional, default 0),
     * ]
     *
     * @throws InsufficientStockException
     */
    public function createTransaction(array $data): Transaction
    {
        $discount = (float) ($data['discount'] ?? 0);
        $tax      = (float) ($data['tax'] ?? 0);

        $productIds = array_column($data['items'], 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($data['items'] as $item) {
            $product  = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];

            if ($product->stock < $quantity) {
                throw new InsufficientStockException($product->name, $product->stock);
            }
        }

        return DB::transaction(function () use ($data, $products, $discount, $tax) {
            $invoiceNumber = $this->generateInvoiceNumber();
            $subtotal      = 0.0;
            $itemsToCreate = [];

            foreach ($data['items'] as $item) {
                $product  = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $price    = (float) $product->sell_price;
                $itemSub  = $this->calculateItemSubtotal($price, $quantity);
                $subtotal += $itemSub;

                $itemsToCreate[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $price,
                    'quantity'     => $quantity,
                    'subtotal'     => $itemSub,
                ];

                $product->decrement('stock', $quantity);
            }

            $total      = $this->calculateTotal($subtotal, $discount, $tax);
            $amountPaid = (float) $data['amount_paid'];
            $change     = $this->calculateChange($amountPaid, $total);

            $transaction = Transaction::create([
                'user_id'        => Auth::id(),
                'invoice_number' => $invoiceNumber,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total'          => $total,
                'payment_method' => $data['payment_method'],
                'amount_paid'    => $amountPaid,
                'change_amount'  => max(0, $change),
                'status'         => TransactionStatus::Completed,
            ]);

            foreach ($itemsToCreate as $itemData) {
                $itemData['transaction_id'] = $transaction->id;
                TransactionItem::create($itemData);
            }

            return $transaction;
        });
    }

    public function cancelTransaction(Transaction $transaction, string $cancelReason): Transaction
    {
        return DB::transaction(function () use ($transaction, $cancelReason) {
            $transaction->update([
                'status'        => TransactionStatus::Cancelled,
                'cancel_reason' => $cancelReason,
            ]);
            
            foreach ($transaction->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }

            return $transaction->fresh();
        });
    }
}
