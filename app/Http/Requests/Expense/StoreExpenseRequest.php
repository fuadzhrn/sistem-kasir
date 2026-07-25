<?php

namespace App\Http\Requests\Expense;

use App\Models\Expense;
use App\Support\Format\Rupiah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) === true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                $this->user()?->isOwner() ? 'required' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'expense_category_id' => [
                'required',
                'integer',
                Rule::exists('expense_categories', 'id')->where('is_active', true),
            ],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'gt:0', 'max:9999999999999999'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'proof' => [
                'nullable',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(3 * 1024),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.prohibited' => 'Admin tidak boleh menentukan cabang melalui request.',
            'branch_id.exists' => 'Cabang tidak tersedia atau sedang nonaktif.',
            'expense_category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'expense_category_id.exists' => 'Kategori pengeluaran tidak tersedia atau sedang nonaktif.',
            'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
            'expense_date.date' => 'Tanggal pengeluaran tidak valid.',
            'expense_date.before_or_equal' => 'Tanggal pengeluaran tidak boleh melewati hari ini.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.integer' => 'Nominal pengeluaran harus berupa Rupiah tanpa desimal.',
            'amount.gt' => 'Nominal pengeluaran harus lebih besar dari Rp0.',
            'description.required' => 'Deskripsi pengeluaran wajib diisi.',
            'description.min' => 'Deskripsi pengeluaran minimal 5 karakter.',
            'description.max' => 'Deskripsi pengeluaran maksimal 1.000 karakter.',
            'proof.image' => 'Bukti pengeluaran harus berupa gambar.',
            'proof.mimes' => 'Bukti hanya boleh berformat JPG, JPEG, PNG, atau WEBP.',
            'proof.max' => 'Ukuran bukti pengeluaran maksimal 3 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => Rupiah::normalizeInput($this->input('amount')),
            'description' => preg_replace('/\s+/u', ' ', trim((string) $this->input('description'))),
        ]);
    }
}
