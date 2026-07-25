<?php

namespace App\Http\Requests\Sale;

use App\Models\Sale;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Sale::class) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();
        $cashierExists = Rule::exists('users', 'id');

        if ($user?->isAdmin()) {
            $cashierExists = $cashierExists->where(
                fn (Builder $query): Builder => $query->where('branch_id', $user->branch_id),
            );
        }

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'branch_id' => [
                $user?->isOwner() ? 'nullable' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id'),
            ],
            'cashier_id' => [
                $user?->isCashier() ? 'prohibited' : 'nullable',
                'integer',
                $cashierExists,
            ],
            'status' => ['nullable', Rule::in(Sale::statuses())],
            'payment_method_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_methods', 'id'),
            ],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Pencarian maksimal 100 karakter.',
            'branch_id.prohibited' => 'Filter cabang hanya tersedia untuk Owner.',
            'branch_id.exists' => 'Cabang yang dipilih tidak tersedia.',
            'cashier_id.prohibited' => 'Kasir hanya dapat melihat transaksi miliknya sendiri.',
            'cashier_id.exists' => 'Pengguna yang dipilih tidak tersedia pada cakupan Anda.',
            'status.in' => 'Status transaksi tidak valid.',
            'payment_method_id.exists' => 'Metode pembayaran yang dipilih tidak tersedia.',
            'date_from.date_format' => 'Tanggal mulai harus menggunakan format tanggal yang valid.',
            'date_to.date_format' => 'Tanggal akhir harus menggunakan format tanggal yang valid.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'per_page.in' => 'Jumlah data per halaman hanya boleh 15, 25, atau 50.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->input('search')) ?: null,
        ]);
    }
}
