<?php

namespace App\Models;

use App\Models\Concerns\HasBranchAccessScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'receipt_number',
    'receipt_date',
    'supplier_name',
    'total_cost',
    'notes',
    'created_by',
])]
class StockReceipt extends Model
{
    use HasBranchAccessScope, HasFactory;

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'total_cost' => 'decimal:2',
        ];
    }
}
