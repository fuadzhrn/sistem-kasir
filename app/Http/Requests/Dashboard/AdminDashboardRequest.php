<?php

namespace App\Http\Requests\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class AdminDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in([
                'today',
                'this_week',
                'this_month',
                'this_year',
                'custom',
            ])],
            'date_from' => [
                'nullable',
                'required_if:period,custom',
                'date',
                'before_or_equal:today',
            ],
            'date_to' => [
                'nullable',
                'required_if:period,custom',
                'date',
                'after_or_equal:date_from',
                'before_or_equal:today',
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($this->input('period') !== 'custom'
                        || ! $this->filled('date_from')
                        || ! $this->filled('date_to')) {
                        return;
                    }

                    try {
                        $start = CarbonImmutable::parse((string) $this->input('date_from'));
                        $end = CarbonImmutable::parse((string) $value);
                    } catch (Throwable) {
                        return;
                    }

                    if ($start->diffInDays($end) + 1 > 366) {
                        $fail('Rentang dashboard maksimal 366 hari.');
                    }
                },
            ],
            'branch_id' => ['prohibited'],
            'cashier_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Periode dashboard wajib dipilih.',
            'period.in' => 'Periode dashboard tidak valid.',
            'date_from.required_if' => 'Tanggal mulai wajib diisi untuk rentang tanggal.',
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_from.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'date_to.required_if' => 'Tanggal selesai wajib diisi untuk rentang tanggal.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'date_to.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
            'branch_id.prohibited' => 'Cabang dashboard ditentukan oleh akun Admin.',
            'cashier_id.prohibited' => 'Dashboard cabang tidak menerima filter Kasir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'period' => $this->input('period', 'this_month'),
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
        ]);
    }
}
