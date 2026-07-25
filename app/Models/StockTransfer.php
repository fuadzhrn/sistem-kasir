<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'transfer_number',
    'from_branch_id',
    'to_branch_id',
    'product_id',
    'quantity',
    'status',
    'unit_cost',
    'source_quantity_before',
    'source_quantity_after',
    'destination_quantity_before',
    'destination_quantity_after',
    'destination_average_cost_before',
    'destination_average_cost_after',
    'notes',
    'requested_by',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'completed_at',
])]
class StockTransfer extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function labels(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::labels()[$this->status] ?? 'Tidak Diketahui';
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwner()) {
            return $query;
        }

        if (! $user->isAdmin() || $user->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $nested) use ($user): void {
            $nested
                ->where($nested->qualifyColumn('from_branch_id'), $user->branch_id)
                ->orWhere($nested->qualifyColumn('to_branch_id'), $user->branch_id);
        });
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
            'unit_cost' => 'decimal:2',
            'source_quantity_before' => 'decimal:3',
            'source_quantity_after' => 'decimal:3',
            'destination_quantity_before' => 'decimal:3',
            'destination_quantity_after' => 'decimal:3',
            'destination_average_cost_before' => 'decimal:2',
            'destination_average_cost_after' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
