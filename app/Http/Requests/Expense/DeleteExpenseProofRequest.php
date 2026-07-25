<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class DeleteExpenseProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $expense = $user === null
            ? null
            : Expense::query()->accessibleTo($user)->whereKey($this->route('expense'))->first();

        return $expense instanceof Expense
            && $user->can('removeProof', $expense);
    }

    public function rules(): array
    {
        return [];
    }
}
