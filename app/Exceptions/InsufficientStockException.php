<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        string $productName = '',
        int $availableStock = 0,
    ) {
        $message = $productName
            ? "Stok tidak mencukupi untuk produk \"{$productName}\". Stok tersedia: {$availableStock}."
            : 'Stok tidak mencukupi.';

        parent::__construct($message);
    }
}
