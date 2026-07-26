<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class StockReceiptReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'number', 'supplier', 'cost'];
    }

    protected function additionalRules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'created_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'supplier' => ['nullable', 'string', 'max:100'],
        ];
    }
}
