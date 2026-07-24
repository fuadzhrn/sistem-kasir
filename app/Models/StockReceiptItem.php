<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_receipt_id',
    'product_id',
    'quantity',
    'purchase_price',
    'subtotal',
])]
class StockReceiptItem extends Model
{
    use HasFactory;

    public function stockReceipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'purchase_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }
}
