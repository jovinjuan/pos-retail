<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{

    public function calculateAverageTransaction(float $totalRevenue, int $transactionCount): float
    {
        if ($transactionCount === 0) {
            return 0.0;
        }

        return $totalRevenue / $transactionCount;
    }


    public function getDailySales(Carbon $startDate, Carbon $endDate): Collection
    {
        $rows = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('COUNT(*) as transaction_count'),
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return $rows->map(function ($row) {
            $row->average_transaction = $this->calculateAverageTransaction(
                (float) $row->total_revenue,
                (int) $row->transaction_count,
            );
            return $row;
        });
    }

    public function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', TransactionStatus::Completed->value)
            ->whereBetween('transactions.created_at', [
                $startDate->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->select(
                'transaction_items.product_id',
                'transaction_items.product_name',
                DB::raw('SUM(transaction_items.quantity) as units_sold'),
                DB::raw('SUM(transaction_items.subtotal) as total_revenue'),
            )
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    public function getCategoryPerformance(Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('transactions.status', TransactionStatus::Completed->value)
            ->whereBetween('transactions.created_at', [
                $startDate->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->select(
                'categories.id as category_id',
                DB::raw("COALESCE(categories.name, 'Tanpa Kategori') as category_name"),
                DB::raw('SUM(transaction_items.quantity) as units_sold'),
                DB::raw('SUM(transaction_items.subtotal) as total_revenue'),
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();
    }

    public function getPaymentMethodDistribution(Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total) as total_revenue'),
            )
            ->groupBy('payment_method')
            ->orderByDesc('transaction_count')
            ->get();
    }
}
