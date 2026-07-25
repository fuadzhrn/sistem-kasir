<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class RejectExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $expense = $user === null
            ? null
            : Expense::query()->accessibleTo($user)->whereKey($this->route('expense'))->first();

        return $expense instanceof Expense
            && $user->can('reject', $expense);
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 1.000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rejection_reason' => preg_replace(
                '/\s+/u',
                ' ',
                trim((string) $this->input('rejection_reason')),
            ),
        ]);
    }
}
