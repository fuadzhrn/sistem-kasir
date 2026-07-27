<?php

namespace App\Http\Requests\Stock;

use App\Models\BranchStock;
use App\Support\Format\Quantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInitialStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', BranchStock::class) === true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                $this->user()?->isOwner() ? 'required' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999.999'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.prohibited' => 'Cabang Admin ditentukan dari akun dan tidak boleh dikirim.',
            'branch_id.exists' => 'Cabang aktif yang dipilih tidak tersedia.',
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk aktif yang dipilih tidak tersedia.',
            'quantity.required' => 'Jumlah stok awal wajib diisi.',
            'quantity.numeric' => 'Jumlah stok harus berupa angka.',
            'quantity.decimal' => 'Jumlah stok maksimal menggunakan tiga angka desimal.',
            'quantity.min' => 'Jumlah stok tidak boleh negatif.',
            'quantity.max' => 'Jumlah stok melebihi batas yang diperbolehkan.',
            'reason.required' => 'Alasan perubahan wajib diisi.',
            'reason.min' => 'Alasan perubahan minimal 5 karakter.',
            'reason.max' => 'Alasan perubahan maksimal 500 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => Quantity::normalizeInput($this->input('quantity')),
            'reason' => trim((string) $this->input('reason')),
        ]);
    }
}
