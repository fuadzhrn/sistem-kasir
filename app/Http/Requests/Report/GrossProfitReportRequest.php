<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class GrossProfitReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'invoice', 'net_sales', 'profit', 'margin'];
    }

    protected function additionalRules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
        ];
    }
}
