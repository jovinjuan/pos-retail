<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'sell_price',
        'cost_price',
        'stock',
        'min_stock',
        'unit',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'sell_price'  => 'decimal:2',
        'cost_price'  => 'decimal:2',
        'stock'       => 'integer',
        'min_stock'   => 'integer',
        'is_active'   => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    protected function formattedSellPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format((float) $this->sell_price, 0, ',', '.'),
        );
    }

    protected function formattedCostPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format((float) $this->cost_price, 0, ',', '.'),
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBelowMinStock($query)
    {
        return $query->whereColumn('stock', '<', 'min_stock');
    }
}
