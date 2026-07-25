<?php

namespace App\Http\Requests\StockAdjustment;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockAdjustment::class) === true;
    }

    public function rules(): array
    {
        $viewer = $this->user();

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'branch_id' => [
                $viewer?->isAdmin() ? 'prohibited' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'adjustment_type' => ['nullable', Rule::in(StockAdjustment::types())],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->when(
                    $viewer?->isAdmin() === true,
                    fn ($query) => $query->where('branch_id', $viewer->branch_id),
                ),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.prohibited' => 'Admin hanya dapat melihat penyesuaian cabang akun.',
            'branch_id.exists' => 'Cabang aktif yang dipilih tidak tersedia.',
            'adjustment_type.in' => 'Jenis penyesuaian tidak valid.',
            'product_id.exists' => 'Produk filter tidak tersedia.',
            'user_id.exists' => 'Pengguna filter tidak tersedia pada akses Anda.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
