<?php

namespace App\Models;

use App\Models\Concerns\HasBranchAccessScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'product_id',
    'created_by',
    'movement_type',
    'reference_type',
    'reference_id',
    'quantity_before',
    'quantity_change',
    'quantity_after',
    'unit_cost',
    'notes',
])]
class StockMovement extends Model
{
    use HasBranchAccessScope, HasFactory;

    public const TYPE_INITIAL = 'initial';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';

    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_VOID_SALE = 'void_sale';

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

    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:3',
            'quantity_change' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }
}
