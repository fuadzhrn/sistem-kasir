<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashierDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCashier() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Sale::statuses())],
            'date_from' => ['nullable', 'date', 'before_or_equal:today'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
                'before_or_equal:today',
            ],
            'per_page' => ['nullable', Rule::in([10, 15, 25])],
            'branch_id' => ['prohibited'],
            'cashier_id' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.max' => 'Pencarian nomor nota maksimal 100 karakter.',
            'status.in' => 'Status transaksi tidak valid.',
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_from.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'date_to.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
            'per_page.in' => 'Jumlah data per halaman tidak valid.',
            'branch_id.prohibited' => 'Cabang ditentukan oleh akun Kasir.',
            'cashier_id.prohibited' => 'Kasir ditentukan oleh akun yang sedang login.',
            'user_id.prohibited' => 'Pengguna ditentukan oleh akun yang sedang login.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search', ''));

        $this->merge([
            'search' => $search !== '' ? $search : null,
            'status' => $this->filled('status') ? $this->input('status') : null,
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'per_page' => $this->input('per_page', 10),
        ]);
    }
}
