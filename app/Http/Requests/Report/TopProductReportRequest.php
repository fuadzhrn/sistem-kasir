<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class TopProductReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['net_sales', 'quantity', 'receipts', 'product'];
    }

    protected function defaultSort(): string
    {
        return 'net_sales';
    }

    protected function additionalRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
        ];
    }
}
