<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'adjustment_number',
    'branch_id',
    'product_id',
    'adjustment_type',
    'quantity',
    'target_quantity',
    'quantity_before',
    'quantity_change',
    'quantity_after',
    'unit_cost',
    'reason',
    'created_by',
])]
class StockAdjustment extends Model
{
    use HasFactory;

    public const TYPE_ADDITION = 'addition';

    public const TYPE_SUBTRACTION = 'subtraction';

    public const TYPE_DAMAGED = 'damaged';

    public const TYPE_LOST = 'lost';

    public const TYPE_CORRECTION = 'correction';

    public static function types(): array
    {
        return [
            self::TYPE_ADDITION,
            self::TYPE_SUBTRACTION,
            self::TYPE_DAMAGED,
            self::TYPE_LOST,
            self::TYPE_CORRECTION,
        ];
    }

    public static function labels(): array
    {
        return [
            self::TYPE_ADDITION => 'Tambah Stok',
            self::TYPE_SUBTRACTION => 'Kurangi Stok',
            self::TYPE_DAMAGED => 'Stok Rusak',
            self::TYPE_LOST => 'Stok Hilang',
            self::TYPE_CORRECTION => 'Koreksi Stok',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::labels()[$this->adjustment_type] ?? 'Penyesuaian';
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwner()) {
            return $query;
        }

        if (! $user->isAdmin() || $user->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($query->qualifyColumn('branch_id'), $user->branch_id);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'target_quantity' => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_change' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }
}
