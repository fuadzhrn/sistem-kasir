<?php

namespace App\Http\Requests\Report;

use Illuminate\Validation\Rule;

class PriceHistoryReportRequest extends BaseReportRequest
{
    protected function sortOptions(): array
    {
        $options = ['date', 'product', 'selling_change'];

        if ($this->user()?->isOwner()) {
            $options[] = 'purchase_change';
        }

        return $options;
    }

    protected function additionalRules(): array
    {
        return [
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'changed_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'change_type' => ['nullable', Rule::in(['selling', 'purchase', 'all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $changeType = $this->input('change_type', 'all');

        if ($this->user()?->isAdmin() && $changeType === 'purchase') {
            $changeType = 'all';
        }

        $this->merge(['change_type' => $changeType]);
    }
}
