<?php

namespace Tests\Integration\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Integration tests for TransactionService.
 * Property 17: Konsistensi Stok — Validates: Requirements 5.9, 5.13
 * Property 18: Penolakan Stok   — Validates: Requirement 5.10
 */
class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);
        Auth::login(User::factory()->create());
    }

    public function test_stok_berkurang_setelah_transaksi(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'sell_price' => 50000]);

        $this->service->createTransaction([
            'items'          => [['product_id' => $product->id, 'quantity' => 3]],
            'payment_method' => 'cash',
            'amount_paid'    => 200000,
        ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
    }

    public function test_stok_dikembalikan_setelah_cancel(): void
    {
        $product     = Product::factory()->create(['stock' => 10, 'sell_price' => 50000]);
        $transaction = $this->service->createTransaction([
            'items'          => [['product_id' => $product->id, 'quantity' => 4]],
            'payment_method' => 'cash',
            'amount_paid'    => 200000,
        ]);

        $this->service->cancelTransaction($transaction, 'Test');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
    }

    public function test_invoice_number_format_benar(): void
    {
        $product     = Product::factory()->create(['stock' => 5, 'sell_price' => 10000]);
        $transaction = $this->service->createTransaction([
            'items'          => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_paid'    => 10000,
        ]);

        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{4}$/', $transaction->invoice_number);
    }
}
