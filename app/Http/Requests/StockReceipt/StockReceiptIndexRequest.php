<?php

namespace App\Http\Requests\StockReceipt;

use App\Models\StockReceipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockReceiptIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockReceipt::class) === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'branch_id' => [
                $this->user()?->isAdmin() ? 'prohibited' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'supplier' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.prohibited' => 'Admin hanya dapat membuka penerimaan cabang akun.',
            'branch_id.exists' => 'Cabang aktif yang dipilih tidak tersedia.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'search.max' => 'Pencarian maksimal 100 karakter.',
            'supplier.max' => 'Filter supplier maksimal 150 karakter.',
        ];
    }
}
