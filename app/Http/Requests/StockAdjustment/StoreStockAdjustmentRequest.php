<?php

namespace App\Http\Requests\StockAdjustment;

use App\Models\StockAdjustment;
use App\Support\Format\Quantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockAdjustment::class) === true;
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
            'adjustment_type' => ['required', Rule::in(StockAdjustment::types())],
            'quantity' => [
                'prohibited_if:adjustment_type,'.StockAdjustment::TYPE_CORRECTION,
                'required_unless:adjustment_type,'.StockAdjustment::TYPE_CORRECTION,
                'nullable',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:999999999.999',
            ],
            'target_quantity' => [
                'prohibited_unless:adjustment_type,'.StockAdjustment::TYPE_CORRECTION,
                'required_if:adjustment_type,'.StockAdjustment::TYPE_CORRECTION,
                'nullable',
                'numeric',
                'decimal:0,3',
                'gte:0',
                'max:999999999.999',
            ],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.prohibited' => 'Cabang Admin ditentukan dari akun dan tidak boleh dikirim.',
            'branch_id.exists' => 'Cabang harus tersedia dan aktif.',
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk harus tersedia dan aktif.',
            'adjustment_type.required' => 'Jenis penyesuaian wajib dipilih.',
            'adjustment_type.in' => 'Jenis penyesuaian tidak valid.',
            'quantity.required_unless' => 'Quantity penyesuaian wajib diisi.',
            'quantity.prohibited_if' => 'Gunakan target quantity untuk koreksi stok.',
            'quantity.decimal' => 'Quantity maksimal menggunakan tiga angka desimal.',
            'quantity.gt' => 'Quantity harus lebih besar dari nol.',
            'target_quantity.required_if' => 'Target quantity wajib diisi untuk koreksi stok.',
            'target_quantity.prohibited_unless' => 'Target quantity hanya digunakan untuk koreksi stok.',
            'target_quantity.decimal' => 'Target quantity maksimal menggunakan tiga angka desimal.',
            'target_quantity.gte' => 'Target quantity tidak boleh negatif.',
            'reason.required' => 'Alasan penyesuaian wajib diisi.',
            'reason.min' => 'Alasan harus berisi minimal 10 karakter.',
            'reason.max' => 'Alasan maksimal 1000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => Quantity::normalizeInput($this->input('quantity')),
            'target_quantity' => Quantity::normalizeInput($this->input('target_quantity')),
            'reason' => trim((string) $this->input('reason')),
        ]);
    }
}
