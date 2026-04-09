<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Produk & Inventori';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Penyesuaian Stok';

    protected static ?string $pluralModelLabel = 'Penyesuaian Stok';

    public static function form(Form $form): Form
    {
    
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity_change')
                    ->label('Perubahan Stok')
                    ->badge()
                    ->color(fn (StockAdjustment $record): string => $record->quantity_change >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state): string => ($state >= 0 ? '+' : '') . $state),

                TextColumn::make('stock_before')
                    ->label('Stok Sebelum')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('stock_after')
                    ->label('Stok Sesudah')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->tooltip(fn (StockAdjustment $record): string => $record->reason),

                TextColumn::make('user.name')
                    ->label('Oleh')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Produk')
                    ->options(Product::active()->pluck('name', 'id'))
                    ->searchable(),

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
            ])
            ->headerActions([
                Action::make('sesuaikan_stok')
                    ->label('Sesuaikan Stok')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Select::make('product_id')
                            ->label('Produk')
                            ->options(Product::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        TextInput::make('quantity_change')
                            ->label('Perubahan Stok')
                            ->helperText('Gunakan nilai positif untuk penambahan, negatif untuk pengurangan.')
                            ->required()
                            ->integer()
                            ->placeholder('+10 atau -5'),

                        Textarea::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, InventoryService $inventoryService): void {
                        $product = Product::findOrFail($data['product_id']);
                        $user    = Auth::user();

                        try {
                            $inventoryService->applyAdjustment(
                                $product,
                                (int) $data['quantity_change'],
                                $data['reason'],
                                $user,
                            );

                            Notification::make()
                                ->title('Stok berhasil disesuaikan')
                                ->body("Stok \"{$product->name}\" diperbarui.")
                                ->success()
                                ->send();
                        } catch (\App\Exceptions\InsufficientStockException $e) {
                            Notification::make()
                                ->title('Stok tidak mencukupi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction(null)   
            ->recordUrl(null);     
    }

    public static function canCreate(): bool
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
            'index' => Pages\ListStockAdjustments::route('/'),
        ];
    }
}
