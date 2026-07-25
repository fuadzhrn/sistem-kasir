<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class ApproveExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $expense = $user === null
            ? null
            : Expense::query()->accessibleTo($user)->whereKey($this->route('expense'))->first();

        return $expense instanceof Expense
            && $user->can('approve', $expense);
    }

    public function rules(): array
    {
        return [];
    }
}
