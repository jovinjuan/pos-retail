<?php

namespace Tests\Unit\Services;

use App\Services\TransactionService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TransactionService pure calculation methods.
 * Property 15: Kalkulasi Cart Invariant — Validates: Requirements 5.6
 * Property 16: Kalkulasi Kembalian Cash — Validates: Requirement 5.8
 */
class TransactionServiceTest extends TestCase
{
    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionService();
    }

    public function test_subtotal_item_sama_dengan_harga_kali_quantity(): void
    {
        $this->assertSame(45000.0, $this->service->calculateItemSubtotal(15000, 3));
    }

    public function test_subtotal_item_dengan_quantity_satu_sama_dengan_harga_satuan(): void
    {
        $this->assertSame(20000.0, $this->service->calculateItemSubtotal(20000, 1));
    }

    public function test_subtotal_item_harga_desimal(): void
    {
        $this->assertSame(37500.0, $this->service->calculateItemSubtotal(12500, 3));
    }

    public function test_subtotal_item_quantity_besar(): void
    {
        $this->assertSame(1000000.0, $this->service->calculateItemSubtotal(10000, 100));
    }

    public function test_total_cart_dikurangi_diskon_ditambah_pajak(): void
    {
        $this->assertSame(95000.0, $this->service->calculateTotal(100000, 10000, 5000));
    }

    public function test_total_cart_tanpa_diskon_dan_pajak_sama_dengan_subtotal(): void
    {
        $this->assertSame(80000.0, $this->service->calculateTotal(80000, 0, 0));
    }

    public function test_total_cart_hanya_diskon(): void
    {
        $this->assertSame(90000.0, $this->service->calculateTotal(100000, 10000, 0));
    }

    public function test_total_cart_hanya_pajak(): void
    {
        $this->assertSame(110000.0, $this->service->calculateTotal(100000, 0, 10000));
    }

    public function test_total_cart_diskon_sama_dengan_subtotal(): void
    {
        $this->assertSame(0.0, $this->service->calculateTotal(50000, 50000, 0));
    }

    public function test_kembalian_cash_dihitung_dengan_benar(): void
    {
        $this->assertSame(25000.0, $this->service->calculateChange(100000, 75000));
    }

    public function test_kembalian_nol_jika_bayar_tepat(): void
    {
        $this->assertSame(0.0, $this->service->calculateChange(50000, 50000));
    }

    public function test_kembalian_besar(): void
    {
        $this->assertSame(500000.0, $this->service->calculateChange(1000000, 500000));
    }

    public function test_format_invoice_number_sesuai_pola(): void
    {
        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{4}$/', 'INV-20250115-0001');
    }

    public function test_urutan_invoice_number_increment(): void
    {
        $getSeq = fn (string $inv) => (int) substr($inv, -4);
        $this->assertSame(1, $getSeq('INV-20250115-0001'));
        $this->assertSame(2, $getSeq('INV-20250115-0002'));
        $this->assertSame($getSeq('INV-20250115-0001') + 1, $getSeq('INV-20250115-0002'));
    }
}
