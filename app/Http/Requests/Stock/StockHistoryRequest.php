<?php

namespace App\Http\Requests\Stock;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockMovement::class) === true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                $this->user()?->isAdmin() ? 'prohibited' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'movement_type' => ['nullable', Rule::in([
                StockMovement::TYPE_INITIAL,
                StockMovement::TYPE_PURCHASE,
                StockMovement::TYPE_SALE,
                StockMovement::TYPE_ADJUSTMENT_IN,
                StockMovement::TYPE_ADJUSTMENT_OUT,
                StockMovement::TYPE_TRANSFER_IN,
                StockMovement::TYPE_TRANSFER_OUT,
                StockMovement::TYPE_VOID_SALE,
            ])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.prohibited' => 'Admin hanya dapat membuka riwayat cabang akun.',
            'branch_id.exists' => 'Cabang aktif yang dipilih tidak tersedia.',
            'product_id.exists' => 'Produk yang dipilih tidak tersedia.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'movement_type.in' => 'Jenis perubahan stok tidak valid.',
            'user_id.exists' => 'Pengguna yang dipilih tidak tersedia.',
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'search.max' => 'Pencarian maksimal 100 karakter.',
        ];
    }
}
