<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailySalesChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Grafik Penjualan Harian';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7'  => '7 Hari Terakhir',
            '14' => '14 Hari Terakhir',
            '30' => '30 Hari Terakhir',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 7);
        $from = Carbon::now()->subDays($days - 1)->startOfDay();
        $to   = Carbon::now()->endOfDay();

        /** @var ReportService $service */
        $service = app(ReportService::class);
        $sales   = $service->getDailySales($from, $to);


        $labels  = [];
        $data    = [];
        $salesMap = $sales->keyBy('date');

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - 1 - $i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');
            $data[]   = isset($salesMap[$date]) ? (float) $salesMap[$date]->total_revenue : 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Pendapatan (Rp)',
                    'data'            => $data,
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
