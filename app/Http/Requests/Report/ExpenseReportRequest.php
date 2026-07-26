<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class ExpenseReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'amount', 'status', 'category'];
    }

    protected function additionalRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')],
            'created_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['status' => $this->input('status', 'all')]);
    }
}
