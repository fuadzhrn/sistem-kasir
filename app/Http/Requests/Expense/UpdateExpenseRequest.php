<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateExpenseRequest extends StoreExpenseRequest
{
    public function authorize(): bool
    {
        $expense = $this->expense();

        return $expense !== null && $this->user()?->can('update', $expense) === true;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['branch_id'] = ['prohibited'];
        $rules['expense_category_id'] = [
            'required',
            'integer',
            Rule::exists('expense_categories', 'id'),
        ];

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('expense_category_id')) {
                    return;
                }

                $expense = $this->expense();
                $categoryId = (int) $this->input('expense_category_id');

                if ($expense instanceof Expense
                    && $categoryId !== (int) $expense->expense_category_id
                    && ! ExpenseCategory::query()->whereKey($categoryId)->where('is_active', true)->exists()) {
                    $validator->errors()->add('expense_category_id', 'Kategori tujuan harus aktif.');
                }
            },
        ];
    }

    private function expense(): ?Expense
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        return Expense::query()
            ->accessibleTo($user)
            ->whereKey($this->route('expense'))
            ->first();
    }
}
