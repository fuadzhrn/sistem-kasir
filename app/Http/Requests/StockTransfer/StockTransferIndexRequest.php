<?php

namespace App\Http\Requests\StockTransfer;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockTransfer::class) === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(StockTransfer::statuses())],
            'branch_id' => [
                $this->user()?->isOwner() ? 'nullable' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id'),
            ],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->input('search')) ?: null,
        ]);
    }
}
