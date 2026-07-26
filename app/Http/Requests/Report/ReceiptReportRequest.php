<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class ReceiptReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        return ['date', 'invoice', 'total', 'status'];
    }

    protected function additionalRules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'status' => ['nullable', Rule::in(['completed', 'void_requested', 'voided', 'all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['status' => $this->input('status', 'all')]);
    }
}
