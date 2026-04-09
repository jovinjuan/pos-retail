<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'user_id',
        'quantity_change',
        'stock_before',
        'stock_after',
        'reason',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'stock_before'    => 'integer',
        'stock_after'     => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
