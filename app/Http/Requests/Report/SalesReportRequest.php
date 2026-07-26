<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class SalesReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'invoice', 'product', 'net_sales'];
    }

    protected function additionalRules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'status' => ['nullable', Rule::in(['completed', 'voided', 'all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['status' => $this->input('status', 'completed')]);
    }
}
