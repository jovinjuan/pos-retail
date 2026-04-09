<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class LaporanExportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function excel(Request $request)
    {
        try {
            $from = Carbon::parse($request->query('from', now()->startOfMonth()));
            $to   = Carbon::parse($request->query('to', now()));

            $topProducts         = $this->reportService->getTopProducts($from, $to);
            $categoryPerformance = $this->reportService->getCategoryPerformance($from, $to);

            $filename = 'laporan-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.xlsx';

            return Excel::download(
                new LaporanExport($topProducts, $categoryPerformance, $from, $to),
                $filename
            );
        } catch (\Throwable $e) {
            abort(500, 'Ekspor Excel gagal: ' . $e->getMessage());
        }
    }

    public function pdf(Request $request)
    {
        try {
            $from = Carbon::parse($request->query('from', now()->startOfMonth()));
            $to   = Carbon::parse($request->query('to', now()));

            $topProducts         = $this->reportService->getTopProducts($from, $to);
            $categoryPerformance = $this->reportService->getCategoryPerformance($from, $to);

            $pdf = Pdf::loadView('exports.laporan-pdf', [
                'topProducts'         => $topProducts,
                'categoryPerformance' => $categoryPerformance,
                'from'                => $from,
                'to'                  => $to,
            ])->setPaper('a4', 'portrait');

            $filename = 'laporan-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            abort(500, 'Ekspor PDF gagal: ' . $e->getMessage());
        }
    }
}
