<?php

namespace App\Http\Requests\Expense;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('expenseCategory');

        return $category instanceof ExpenseCategory
            && $this->user()?->can('updateStatus', $category) === true;
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
