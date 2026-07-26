<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class StockMovementReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'product', 'type'];
    }

    protected function additionalRules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'created_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'movement_type' => ['nullable', 'string', 'max:50'],
            'reference_type' => ['nullable', 'string', 'max:150'],
        ];
    }
}
