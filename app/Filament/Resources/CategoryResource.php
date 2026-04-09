<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Produk & Inventori';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama Kategori')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true),

            TextInput::make('slug')
                ->label('Slug')
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->helperText('Slug di-generate otomatis dari nama kategori.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('products_count')
                    ->label('Jumlah Produk')
                    ->counts('products')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(fn (Category $record) => 'Hapus Kategori: ' . $record->name)
                    ->modalDescription(function (Category $record) {
                        $activeCount = $record->products()->where('is_active', true)->count();
                        if ($activeCount > 0) {
                            return "Kategori ini memiliki {$activeCount} produk aktif. Setelah dihapus, semua produk tersebut akan kehilangan kategorinya (category_id menjadi NULL). Lanjutkan?";
                        }
                        return 'Apakah Anda yakin ingin menghapus kategori ini?';
                    })
                    ->action(function (Category $record) {
                        // Nullify category_id on related products, then delete
                        $record->products()->update(['category_id' => null]);
                        $record->delete();

                        Notification::make()
                            ->title('Kategori berhasil dihapus')
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
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
