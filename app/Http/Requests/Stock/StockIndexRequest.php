<?php

namespace App\Http\Requests\Stock;

use App\Models\BranchStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', BranchStock::class) === true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                $this->user()?->isAdmin() ? 'prohibited' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', Rule::in(['safe', 'low', 'out'])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.prohibited' => 'Admin hanya dapat membuka stok cabang akun.',
            'branch_id.exists' => 'Cabang aktif yang dipilih tidak tersedia.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'status.in' => 'Status stok tidak valid.',
            'search.max' => 'Pencarian maksimal 100 karakter.',
        ];
    }
}
