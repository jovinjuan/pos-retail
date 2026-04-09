<?php

namespace Tests\Integration\Pages;

use App\Exceptions\InsufficientStockException;
use App\Filament\Pages\KasirPage;
use App\Models\Product;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Integration tests for KasirPage.
 * Property 14: Pencarian Produk Aktif — Validates: Requirements 5.2, 5.14
 * Property 15: Kalkulasi Cart        — Validates: Requirement 5.6
 * Property 16: Kembalian Cash        — Validates: Requirement 5.8
 * Property 18: Penolakan Stok        — Validates: Requirement 5.10
 */
class KasirPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Auth::login(User::factory()->create());
    }

    public function test_pencarian_hanya_mengembalikan_produk_aktif(): void
    {
        Product::factory()->create(['name' => 'Produk Aktif', 'is_active' => true]);
        Product::factory()->create(['name' => 'Produk Nonaktif', 'is_active' => false]);

        $page = new KasirPage();
        $page->searchQuery = 'Produk';
        $page->searchProduct();

        $this->assertCount(1, $page->searchResults);
    }

    public function test_subtotal_item_benar(): void
    {
        $product = Product::factory()->create(['sell_price' => 25000, 'is_active' => true, 'stock' => 10]);

        $page = new KasirPage();
        $page->addToCart($product->id);
        $page->updateQuantity($product->id, 3);

        $item = collect($page->cartItems)->firstWhere('product_id', $product->id);
        $this->assertEquals(75000.0, $item['subtotal']);
    }

    public function test_kembalian_cash_benar(): void
    {
        $product = Product::factory()->create(['sell_price' => 75000, 'is_active' => true, 'stock' => 5]);

        $page                = new KasirPage();
        $page->paymentMethod = 'cash';
        $page->amountPaid    = 100000;
        $page->addToCart($product->id);

        $this->assertEquals(25000.0, $page->changeAmount);
    }

    public function test_checkout_ditolak_jika_stok_tidak_mencukupi(): void
    {
        $product = Product::factory()->create(['sell_price' => 10000, 'is_active' => true, 'stock' => 2]);

        $this->expectException(InsufficientStockException::class);

        app(TransactionService::class)->createTransaction([
            'items'          => [['product_id' => $product->id, 'quantity' => 5]],
            'payment_method' => 'cash',
            'amount_paid'    => 50000,
        ]);
    }
}
