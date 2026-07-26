<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class StockReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['status', 'product', 'quantity', 'branch'];
    }

    protected function defaultSort(): string
    {
        return 'status';
    }

    protected function additionalRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'stock_status' => ['nullable', Rule::in(['out', 'low', 'safe'])],
            'product_status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
        ];
    }
}
