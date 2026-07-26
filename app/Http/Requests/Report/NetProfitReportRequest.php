<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class NetProfitReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['period', 'branch', 'net_sales', 'net_profit'];
    }

    protected function defaultSort(): string
    {
        return 'period';
    }

    protected function additionalRules(): array
    {
        return ['granularity' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])]];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['granularity' => $this->input('granularity', 'daily')]);
    }
}
