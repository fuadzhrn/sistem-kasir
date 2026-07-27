<?php

namespace App\Http\Requests\StockTransfer;

use App\Models\StockTransfer;
use App\Support\Format\Quantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockTransfer::class) === true;
    }

    public function rules(): array
    {
        return [
            'from_branch_id' => [
                $this->user()?->isOwner() ? 'required' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'to_branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
                Rule::notIn([$this->sourceBranchId()]),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:999999999.999',
            ],
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_branch_id.required' => 'Cabang asal wajib dipilih.',
            'from_branch_id.prohibited' => 'Cabang asal Admin ditentukan dari akun.',
            'from_branch_id.exists' => 'Cabang asal harus tersedia dan aktif.',
            'to_branch_id.required' => 'Cabang tujuan wajib dipilih.',
            'to_branch_id.exists' => 'Cabang tujuan harus tersedia dan aktif.',
            'to_branch_id.not_in' => 'Cabang tujuan harus berbeda dari cabang asal.',
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk harus tersedia dan aktif.',
            'quantity.required' => 'Quantity mutasi wajib diisi.',
            'quantity.decimal' => 'Quantity maksimal menggunakan tiga angka desimal.',
            'quantity.gt' => 'Quantity harus lebih besar dari nol.',
            'notes.required' => 'Catatan mutasi wajib diisi.',
            'notes.min' => 'Catatan harus berisi minimal 10 karakter.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => Quantity::normalizeInput($this->input('quantity')),
            'notes' => trim((string) $this->input('notes')),
        ]);
    }

    private function sourceBranchId(): ?int
    {
        $value = $this->user()?->isOwner()
            ? $this->input('from_branch_id')
            : $this->user()?->branch_id;

        return is_numeric($value) ? (int) $value : null;
    }
}
