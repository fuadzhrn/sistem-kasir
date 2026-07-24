<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'product_id',
    'product_code',
    'product_name',
    'unit_name',
    'product_size',
    'quantity',
    'selling_price',
    'cost_price',
    'discount_amount',
    'subtotal',
    'profit',
])]
class SaleItem extends Model
{
    use HasFactory;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'selling_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }
}
