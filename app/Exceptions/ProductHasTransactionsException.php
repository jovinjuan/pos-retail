<?php

namespace App\Exceptions;

use Exception;

class ProductHasTransactionsException extends Exception
{
    public function __construct(string $productName = '')
    {
        $message = $productName
            ? "Produk \"{$productName}\" tidak dapat dihapus karena terdapat riwayat transaksi yang terkait."
            : 'Produk tidak dapat dihapus karena terdapat riwayat transaksi yang terkait.';

        parent::__construct($message);
    }
}
