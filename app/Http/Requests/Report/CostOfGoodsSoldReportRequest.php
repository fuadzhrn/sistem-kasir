<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class CostOfGoodsSoldReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'invoice', 'product', 'cost'];
    }

    protected function additionalRules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }
}
