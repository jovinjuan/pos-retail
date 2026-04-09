<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Validate that applying $change to $currentStock won't result in negative stock.
     *
     * @throws InsufficientStockException
     */
    public function validateAdjustment(int $currentStock, int $change): void
    {
        if ($currentStock + $change < 0) {
            throw new InsufficientStockException();
        }
    }

    /**
     * Apply a stock adjustment to a product, recording the history entry.
     * All writes are wrapped in a DB transaction.
     *
     * @throws InsufficientStockException
     */
    public function applyAdjustment(Product $product, int $change, string $reason, User $user): StockAdjustment
    {
        $this->validateAdjustment($product->stock, $change);

        return DB::transaction(function () use ($product, $change, $reason, $user) {
            $stockBefore = $product->stock;
            $stockAfter  = $stockBefore + $change;

            $product->stock = $stockAfter;
            $product->save();

            return StockAdjustment::create([
                'product_id'      => $product->id,
                'user_id'         => $user->id,
                'quantity_change' => $change,
                'stock_before'    => $stockBefore,
                'stock_after'     => $stockAfter,
                'reason'          => $reason,
            ]);
        });
    }
}
