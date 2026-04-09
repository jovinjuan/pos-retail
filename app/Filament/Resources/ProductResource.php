<?php

namespace App\Filament\Resources;

use App\Exceptions\ProductHasTransactionsException;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Produk & Inventori';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama Produk')
                ->required()
                ->maxLength(255),

            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100),

            Select::make('category_id')
                ->label('Kategori')
                ->options(Category::pluck('name', 'id'))
                ->searchable()
                ->nullable(),

            TextInput::make('sell_price')
                ->label('Harga Jual')
                ->required()
                ->numeric()
                ->prefix('Rp')
                ->minValue(0),

            TextInput::make('cost_price')
                ->label('Harga Modal')
                ->required()
                ->numeric()
                ->prefix('Rp')
                ->minValue(0),

            TextInput::make('stock')
                ->label('Stok')
                ->required()
                ->integer()
                ->minValue(0)
                ->default(0),

            TextInput::make('min_stock')
                ->label('Stok Minimum')
                ->required()
                ->integer()
                ->minValue(0)
                ->default(0),

            TextInput::make('unit')
                ->label('Satuan')
                ->required()
                ->maxLength(50)
                ->placeholder('pcs, kg, liter, ...'),

            FileUpload::make('image_path')
                ->label('Gambar Produk')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->directory('products')
                ->nullable(),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('sell_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $record): string => $record->stock < $record->min_stock ? 'danger' : 'success')
                    ->suffix(fn (Product $record): string => $record->stock < $record->min_stock ? ' ⚠' : ''),

                TextColumn::make('unit')
                    ->label('Satuan'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(Category::pluck('name', 'id'))
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                Filter::make('below_min_stock')
                    ->label('Stok di Bawah Minimum')
                    ->query(fn (Builder $query) => $query->belowMinStock()),

                Filter::make('normal_stock')
                    ->label('Stok Normal')
                    ->query(fn (Builder $query) => $query->whereColumn('stock', '>=', 'min_stock')),
            ])
            ->actions([
                EditAction::make()
                    ->before(function (Product $record, array $data, EditAction $action) {
                        // If deactivating, check if product is in any active cart session
                        // Cart sessions are stored in Livewire component state (not DB),
                        // so we rely on the KasirPage to expose active cart product IDs via cache/session.
                        if (isset($data['is_active']) && $data['is_active'] === false && $record->is_active === true) {
                            $activeCartProductIds = cache()->get('active_cart_product_ids', []);
                            if (in_array($record->id, $activeCartProductIds)) {
                                Notification::make()
                                    ->title('Tidak dapat menonaktifkan produk')
                                    ->body("Produk \"{$record->name}\" sedang ada di dalam sesi kasir yang aktif.")
                                    ->danger()
                                    ->send();
                                $action->halt();
                            }
                        }
                    }),

                DeleteAction::make()
                    ->action(function (Product $record, DeleteAction $action) {
                        if ($record->transactionItems()->exists()) {
                            Notification::make()
                                ->title('Tidak dapat menghapus produk')
                                ->body("Produk \"{$record->name}\" tidak dapat dihapus karena terdapat riwayat transaksi yang terkait.")
                                ->danger()
                                ->send();
                            $action->halt();
                            return;
                        }
                        $record->delete();
                        Notification::make()
                            ->title('Produk berhasil dihapus')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
