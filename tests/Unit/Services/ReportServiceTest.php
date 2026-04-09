<?php

namespace Tests\Unit\Services;

use App\Services\ReportService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReportService::calculateAverageTransaction.
 * Property 22: Konsistensi Data Laporan — Validates: Requirement 7.2
 */
class ReportServiceTest extends TestCase
{
    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
    }

    public function test_rata_rata_transaksi_dihitung_dengan_benar(): void
    {
        $this->assertSame(50000.0, $this->service->calculateAverageTransaction(150000, 3));
    }

    public function test_rata_rata_transaksi_nol_jika_tidak_ada_transaksi(): void
    {
        $this->assertSame(0.0, $this->service->calculateAverageTransaction(0, 0));
    }

    public function test_rata_rata_transaksi_satu_transaksi(): void
    {
        $this->assertSame(75000.0, $this->service->calculateAverageTransaction(75000, 1));
    }

    public function test_rata_rata_transaksi_banyak_transaksi(): void
    {
        $this->assertSame(25000.0, $this->service->calculateAverageTransaction(250000, 10));
    }
}
