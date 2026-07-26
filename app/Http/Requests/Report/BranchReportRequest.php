<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class BranchReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['branch', 'net_sales', 'net_profit', 'receipts'];
    }

    protected function defaultSort(): string
    {
        return 'net_sales';
    }

    protected function additionalRules(): array
    {
        return ['branch_status' => ['nullable', Rule::in(['active', 'inactive', 'all'])]];
    }
}
