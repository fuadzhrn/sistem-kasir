<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'branch_id',
    'requested_by',
    'voided_by',
    'voided_at',
    'reason',
    'original_subtotal',
    'original_discount_amount',
    'original_total',
    'original_total_cost',
    'original_gross_profit',
    'payment_method_name',
    'refund_confirmed',
    'notes',
    'status',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
])]
class SaleVoid extends Model
{
    use HasFactory;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected static function booted(): void
    {
        static::updating(fn (): bool => false);
        static::deleting(fn (): bool => false);
    }

    protected function casts(): array
    {
        return [
            'voided_at' => 'datetime',
            'original_subtotal' => 'decimal:2',
            'original_discount_amount' => 'decimal:2',
            'original_total' => 'decimal:2',
            'original_total_cost' => 'decimal:2',
            'original_gross_profit' => 'decimal:2',
            'refund_confirmed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }
}
