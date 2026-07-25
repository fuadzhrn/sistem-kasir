<?php

namespace App\Http\Requests\Cashier;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashierProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Sale::class) === true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                $this->user()?->isOwner() ? 'required' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:40'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search'));
        $this->merge(['search' => $search === '' ? null : $search]);
    }
}
