<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class CashierReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['cashier', 'net_sales', 'receipts', 'average'];
    }

    protected function defaultSort(): string
    {
        return 'net_sales';
    }

    protected function additionalRules(): array
    {
        return [
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'user_status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
        ];
    }
}
