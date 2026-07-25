<?php

namespace App\Http\Requests\Expense;

use App\Models\ExpenseCategory;

class UpdateExpenseCategoryRequest extends StoreExpenseCategoryRequest
{
    public function authorize(): bool
    {
        $category = $this->route('expenseCategory');

        return $category instanceof ExpenseCategory
            && $this->user()?->can('update', $category) === true;
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        $category = $this->route('expenseCategory');

        return parent::nameExists($category instanceof ExpenseCategory ? (int) $category->getKey() : null);
    }
}
