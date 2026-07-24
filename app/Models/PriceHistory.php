<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'changed_by',
    'old_purchase_price',
    'new_purchase_price',
    'old_selling_price',
    'new_selling_price',
    'reason',
    'changed_at',
])]
class PriceHistory extends Model
{
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'old_purchase_price' => 'decimal:2',
            'new_purchase_price' => 'decimal:2',
            'old_selling_price' => 'decimal:2',
            'new_selling_price' => 'decimal:2',
            'changed_at' => 'datetime',
        ];
    }
}
