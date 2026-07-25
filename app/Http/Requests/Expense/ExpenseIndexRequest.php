<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Expense::class) === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id'),
            ],
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'status' => ['nullable', Rule::in([
                Expense::STATUS_PENDING,
                Expense::STATUS_APPROVED,
                Expense::STATUS_REJECTED,
            ])],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search'));
        $data = ['search' => $search === '' ? null : $search];

        if (! $this->user()?->isOwner()) {
            $data['branch_id'] = null;
        }

        $this->merge($data);
    }
}
