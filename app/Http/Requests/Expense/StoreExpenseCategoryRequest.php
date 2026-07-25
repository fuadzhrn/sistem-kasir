<?php

namespace App\Http\Requests\Expense;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExpenseCategory::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('name') && $this->nameExists()) {
                    $validator->errors()->add('name', 'Nama kategori pengeluaran sudah digunakan.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori pengeluaran wajib diisi.',
            'name.max' => 'Nama kategori pengeluaran maksimal 150 karakter.',
            'description.max' => 'Deskripsi kategori maksimal 500 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = trim((string) $this->input('description'));
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'description' => $description === '' ? null : $description,
        ]);
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        return ExpenseCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
