<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'cashier_id',
    'payment_method_id',
    'invoice_number',
    'transaction_date',
    'subtotal',
    'discount_amount',
    'total',
    'amount_paid',
    'change_amount',
    'total_cost',
    'gross_profit',
    'payment_method_name',
    'status',
    'notes',
    'voided_at',
])]
class Sale extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOID_REQUESTED = 'void_requested';

    public const STATUS_VOIDED = 'voided';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleVoids(): HasMany
    {
        return $this->hasMany(SaleVoid::class);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwner()) {
            return $query;
        }

        if ($user->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        $query->where(
            $query->getModel()->qualifyColumn('branch_id'),
            $user->branch_id,
        );

        if ($user->isCashier()) {
            $query->where(
                $query->getModel()->qualifyColumn('cashier_id'),
                $user->getKey(),
            );
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }
}
