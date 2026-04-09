<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Services\TransactionService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'POS System';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Informasi Transaksi')->schema([
                TextEntry::make('invoice_number')->label('No. Invoice'),
                TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y, H:i'),
                TextEntry::make('payment_method')->label('Metode Pembayaran')
                    ->formatStateUsing(fn (PaymentMethod $state): string => match ($state) {
                        PaymentMethod::Cash     => 'Cash',
                        PaymentMethod::Transfer => 'Transfer',
                        PaymentMethod::Qris     => 'QRIS',
                    }),
                TextEntry::make('status')->label('Status')
                    ->badge()
                    ->color(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::Completed => 'success',
                        TransactionStatus::Pending   => 'warning',
                        TransactionStatus::Cancelled => 'danger',
                    }),
                TextEntry::make('cancel_reason')->label('Alasan Pembatalan')
                    ->visible(fn (Transaction $record): bool => $record->status === TransactionStatus::Cancelled)
                    ->placeholder('—'),
            ])->columns(2),

            Section::make('Item Transaksi')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('product_name')->label('Produk'),
                    TextEntry::make('unit_price')->label('Harga Satuan')->money('IDR'),
                    TextEntry::make('quantity')->label('Qty'),
                    TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                ])->columns(4),
            ]),

            Section::make('Ringkasan Pembayaran')->schema([
                TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                TextEntry::make('discount')->label('Diskon')->money('IDR'),
                TextEntry::make('tax')->label('Pajak')->money('IDR'),
                TextEntry::make('total')->label('Total')->money('IDR')->weight('bold'),
                TextEntry::make('amount_paid')->label('Dibayar')->money('IDR'),
                TextEntry::make('change_amount')->label('Kembalian')->money('IDR'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => match ($state) {
                        PaymentMethod::Cash     => 'Cash',
                        PaymentMethod::Transfer => 'Transfer',
                        PaymentMethod::Qris     => 'QRIS',
                    })
                    ->color(fn (PaymentMethod $state): string => match ($state) {
                        PaymentMethod::Cash     => 'success',
                        PaymentMethod::Transfer => 'info',
                        PaymentMethod::Qris     => 'warning',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::Completed => 'Selesai',
                        TransactionStatus::Pending   => 'Pending',
                        TransactionStatus::Cancelled => 'Dibatalkan',
                    })
                    ->color(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::Completed => 'success',
                        TransactionStatus::Pending   => 'warning',
                        TransactionStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),

                SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash'     => 'Cash',
                        'transfer' => 'Transfer',
                        'qris'     => 'QRIS',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Selesai',
                        'pending'   => 'Pending',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Transaction $record): bool => $record->status === TransactionStatus::Completed)
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Transaksi')
                    ->modalDescription('Stok produk akan dikembalikan setelah pembatalan.')
                    ->form([
                        Textarea::make('cancel_reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Transaction $record, array $data, TransactionService $transactionService): void {
                        try {
                            $transactionService->cancelTransaction($record, $data['cancel_reason']);

                            Notification::make()
                                ->title('Transaksi berhasil dibatalkan')
                                ->body("Invoice {$record->invoice_number} telah dibatalkan dan stok dikembalikan.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal membatalkan transaksi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction('view')
            ->recordUrl(null);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view'  => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
