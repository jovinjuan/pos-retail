<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientStockException;
use App\Services\InventoryService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryService::validateAdjustment.
 * Property 12: Penolakan Stok Negatif — Validates: Requirement 4.3
 */
class InventoryServiceTest extends TestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
    }

    public function test_menolak_pengurangan_melebihi_stok_saat_ini(): void
    {
        $this->expectException(InsufficientStockException::class);
        $this->service->validateAdjustment(currentStock: 5, change: -10);
    }

    public function test_menolak_pengurangan_jika_stok_nol(): void
    {
        $this->expectException(InsufficientStockException::class);
        $this->service->validateAdjustment(currentStock: 0, change: -1);
    }

    public function test_menolak_pengurangan_lebih_dari_satu_di_stok_nol(): void
    {
        $this->expectException(InsufficientStockException::class);
        $this->service->validateAdjustment(currentStock: 0, change: -100);
    }

    public function test_menolak_pengurangan_satu_lebih_dari_stok(): void
    {
        $this->expectException(InsufficientStockException::class);
        $this->service->validateAdjustment(currentStock: 3, change: -4);
    }

    public function test_mengizinkan_penambahan_stok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validateAdjustment(currentStock: 0, change: 10);
    }

    public function test_mengizinkan_penambahan_ke_stok_yang_sudah_ada(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validateAdjustment(currentStock: 5, change: 5);
    }

    public function test_mengizinkan_pengurangan_tepat_sama_dengan_stok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validateAdjustment(currentStock: 5, change: -5);
    }

    public function test_mengizinkan_pengurangan_kurang_dari_stok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->service->validateAdjustment(currentStock: 10, change: -3);
    }
}
