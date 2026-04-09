<?php

namespace Tests\Integration\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for InventoryService.
 * Property 11: Immutability — Validates: Requirements 4.2, 4.4
 * Property 12: Stok Negatif — Validates: Requirement 4.3
 */
class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    public function test_apply_adjustment_menyimpan_semua_field(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $adj = $this->service->applyAdjustment($product, 5, 'Restock', $user);

        $this->assertDatabaseHas('stock_adjustments', [
            'id'              => $adj->id,
            'quantity_change' => 5,
            'stock_before'    => 10,
            'stock_after'     => 15,
        ]);
    }

    public function test_menolak_jika_stok_akan_negatif(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->expectException(InsufficientStockException::class);
        $this->service->applyAdjustment($product, -10, 'Berlebih', $user);
    }

    public function test_stok_tidak_berubah_jika_ditolak(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        try {
            $this->service->applyAdjustment($product, -10, 'Berlebih', $user);
        } catch (InsufficientStockException) {}

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertSame(0, StockAdjustment::where('product_id', $product->id)->count());
    }
}
