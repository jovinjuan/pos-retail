<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\TransactionItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Produk Terlaris Hari Ini';

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model $record): string
    {
        return (string) $record->product_id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable(),

                Tables\Columns\TextColumn::make('units_sold')
                    ->label('Unit Terjual')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Pendapatan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->defaultSort('units_sold', 'desc')
            ->paginated(false);
    }

    private function getQuery(): Builder
    {
        $today = Carbon::today();

        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', TransactionStatus::Completed->value)
            ->whereDate('transactions.created_at', $today)
            ->select(
                'transaction_items.product_id',
                'transaction_items.product_name',
                DB::raw('SUM(transaction_items.quantity) as units_sold'),
                DB::raw('SUM(transaction_items.subtotal) as total_revenue'),
            )
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('units_sold');
    }
}
