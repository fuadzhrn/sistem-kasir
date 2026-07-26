<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class SaleVoidReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'invoice', 'total', 'profit'];
    }

    protected function additionalRules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'voided_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
        ];
    }
}
