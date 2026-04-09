<?php

namespace Tests\Integration\Services;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Integration tests for ReportService.
 * Property 22: Konsistensi Data Laporan — Validates: Requirements 7.2, 7.3, 7.4, 7.5
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportService::class);
        $this->user    = User::factory()->create();
    }

    private function createCompletedTransaction(array $items, PaymentMethod $method = PaymentMethod::Cash, ?Carbon $createdAt = null): Transaction
    {
        $subtotal    = collect($items)->sum(fn ($i) => $i['product']->sell_price * $i['quantity']);
        $transaction = Transaction::factory()->create([
            'user_id'        => $this->user->id,
            'status'         => TransactionStatus::Completed,
            'payment_method' => $method,
            'subtotal'       => $subtotal,
            'total'          => $subtotal,
            'amount_paid'    => $subtotal,
            'created_at'     => $createdAt ?? now(),
        ]);
        foreach ($items as $item) {
            TransactionItem::factory()->create([
                'transaction_id' => $transaction->id,
                'product_id'     => $item['product']->id,
                'product_name'   => $item['product']->name,
                'unit_price'     => $item['product']->sell_price,
                'quantity'       => $item['quantity'],
                'subtotal'       => $item['product']->sell_price * $item['quantity'],
            ]);
        }
        return $transaction;
    }

    public function test_daily_sales_agregat_benar(): void
    {
        $today   = Carbon::today();
        $product = Product::factory()->create(['sell_price' => 50000]);

        $this->createCompletedTransaction([['product' => $product, 'quantity' => 1]], createdAt: $today);
        $this->createCompletedTransaction([['product' => $product, 'quantity' => 3]], createdAt: $today);

        $results = $this->service->getDailySales($today, $today);

        $this->assertCount(1, $results);
        $this->assertEquals(2, (int) $results->first()->transaction_count);
        $this->assertEquals(200000.0, (float) $results->first()->total_revenue);
    }

    public function test_daily_sales_mengabaikan_transaksi_cancelled(): void
    {
        $today   = Carbon::today();
        $product = Product::factory()->create(['sell_price' => 50000]);

        $this->createCompletedTransaction([['product' => $product, 'quantity' => 1]], createdAt: $today);
        Transaction::factory()->create(['user_id' => $this->user->id, 'status' => TransactionStatus::Cancelled, 'total' => 999999, 'created_at' => $today]);

        $results = $this->service->getDailySales($today, $today);

        $this->assertEquals(1, (int) $results->first()->transaction_count);
    }

    public function test_top_products_diurutkan_berdasarkan_unit_terjual(): void
    {
        $today    = Carbon::today();
        $productA = Product::factory()->create(['name' => 'Produk A', 'sell_price' => 10000]);
        $productB = Product::factory()->create(['name' => 'Produk B', 'sell_price' => 20000]);

        $this->createCompletedTransaction([['product' => $productA, 'quantity' => 2]], createdAt: $today);
        $this->createCompletedTransaction([['product' => $productB, 'quantity' => 5]], createdAt: $today);

        $results = $this->service->getTopProducts($today, $today);

        $this->assertEquals('Produk B', $results->first()->product_name);
        $this->assertEquals(5, (int) $results->first()->units_sold);
    }

    public function test_category_performance_agregat_per_kategori(): void
    {
        $today    = Carbon::today();
        $category = Category::factory()->create(['name' => 'Elektronik']);
        $product  = Product::factory()->create(['category_id' => $category->id, 'sell_price' => 100000]);

        $this->createCompletedTransaction([['product' => $product, 'quantity' => 2]], createdAt: $today);

        $results = $this->service->getCategoryPerformance($today, $today);

        $this->assertCount(1, $results);
        $this->assertEquals('Elektronik', $results->first()->category_name);
        $this->assertEquals(200000.0, (float) $results->first()->total_revenue);
    }

    public function test_payment_method_distribution_benar(): void
    {
        $today   = Carbon::today();
        $product = Product::factory()->create(['sell_price' => 50000]);

        $this->createCompletedTransaction([['product' => $product, 'quantity' => 1]], PaymentMethod::Cash, $today);
        $this->createCompletedTransaction([['product' => $product, 'quantity' => 1]], PaymentMethod::Cash, $today);
        $this->createCompletedTransaction([['product' => $product, 'quantity' => 1]], PaymentMethod::Qris, $today);

        $results = $this->service->getPaymentMethodDistribution($today, $today);

        $cash = $results->firstWhere('payment_method', PaymentMethod::Cash->value);
        $this->assertEquals(2, (int) $cash->transaction_count);
    }
}
