<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'invoice_number',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'amount_paid',
        'change_amount',
        'status',
        'cancel_reason',
    ];

    protected $casts = [
        'status'         => TransactionStatus::class,
        'payment_method' => PaymentMethod::class,
        'subtotal'       => 'decimal:2',
        'discount'       => 'decimal:2',
        'tax'            => 'decimal:2',
        'total'          => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'change_amount'  => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    protected function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format((float) $this->total, 0, ',', '.'),
        );
    }
}
