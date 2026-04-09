<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PaymentMethodWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Distribusi Metode Pembayaran';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7'  => '7 Hari Terakhir',
            '30' => '30 Hari Terakhir',
            '90' => '90 Hari Terakhir',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 30);
        $from = Carbon::now()->subDays($days - 1)->startOfDay();
        $to   = Carbon::now()->endOfDay();

        /** @var ReportService $service */
        $service      = app(ReportService::class);
        $distribution = $service->getPaymentMethodDistribution($from, $to);

        $labels = [];
        $data   = [];
        $colors = [
            'cash'     => '#10b981',
            'transfer' => '#3b82f6',
            'qris'     => '#f59e0b',
        ];
        $backgroundColors = [];

        foreach ($distribution as $row) {
            $method         = is_object($row->payment_method)
                ? $row->payment_method->value
                : (string) $row->payment_method;
            $labels[]           = ucfirst($method);
            $data[]             = (int) $row->transaction_count;
            $backgroundColors[] = $colors[$method] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Transaksi',
                    'data'            => $data,
                    'backgroundColor' => $backgroundColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
