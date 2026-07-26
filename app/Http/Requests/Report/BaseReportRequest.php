<?php

namespace App\Http\Requests\Report;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

abstract class BaseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()->hasAnyRole(['owner', 'admin']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(['today', 'this_week', 'this_month', 'this_year', 'custom'])],
            'date_from' => ['nullable', 'required_if:period,custom', 'date', 'before_or_equal:today'],
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

                    if ($start->diffInDays($end) > 1826) {
                        $fail('Rentang laporan maksimal lima tahun dalam satu tampilan. Silakan bagi laporan menjadi beberapa periode.');
                    }
                },
            ],
            'branch_id' => $this->user()?->isAdmin()
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('branches', 'id')],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in($this->sortOptions())],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in([25, 50, 100])],
            ...$this->additionalRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'period.in' => 'Periode laporan tidak valid.',
            'date_from.required_if' => 'Tanggal mulai wajib diisi untuk rentang custom.',
            'date_to.required_if' => 'Tanggal selesai wajib diisi untuk rentang custom.',
            'date_from.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'date_to.before_or_equal' => 'Tanggal selesai tidak boleh melewati hari ini.',
            'branch_id.prohibited' => 'Cabang laporan ditentukan oleh akun Admin.',
            'branch_id.exists' => 'Cabang yang dipilih tidak tersedia.',
            'search.max' => 'Pencarian maksimal 100 karakter.',
            'sort.in' => 'Kolom pengurutan tidak valid.',
            'direction.in' => 'Arah pengurutan tidak valid.',
            'per_page.in' => 'Jumlah baris per halaman harus 25, 50, atau 100.',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortOptions(): array
    {
        return ['date'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function additionalRules(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search', ''));

        $this->merge([
            'period' => $this->input('period', 'this_month'),
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'search' => $search !== '' ? $search : null,
            'sort' => $this->input('sort', $this->defaultSort()),
            'direction' => $this->input('direction', 'desc'),
            'per_page' => $this->input('per_page', 25),
        ]);
    }

    protected function defaultSort(): string
    {
        return 'date';
    }
}
