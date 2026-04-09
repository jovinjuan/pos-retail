<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today     = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        $todayQuery = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereDate('created_at', $today);

        $totalRevenueToday    = (float) (clone $todayQuery)->sum('total');
        $transactionCountToday = (int)  (clone $todayQuery)->count();

        $totalRevenueMonth = (float) Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('total');

        return [
            Stat::make('Total Pendapatan Hari Ini', 'Rp ' . number_format($totalRevenueToday, 0, ',', '.'))
                ->description('Transaksi selesai hari ini')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Jumlah Transaksi Hari Ini', $transactionCountToday)
                ->description('Transaksi selesai hari ini')
                ->color('primary')
                ->icon('heroicon-o-shopping-bag'),

            Stat::make('Total Pendapatan Bulan Ini', 'Rp ' . number_format($totalRevenueMonth, 0, ',', '.'))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->color('warning')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
