<?php

namespace App\Http\Requests\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PaymentMethod::class) === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', 'unique:payment_methods,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['cash', 'non_cash', 'other'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('name') && $this->nameExists()) {
                    $validator->errors()->add('name', 'Nama metode pembayaran sudah digunakan.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode metode pembayaran wajib diisi.',
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, garis bawah, atau tanda hubung.',
            'code.unique' => 'Kode metode pembayaran sudah digunakan.',
            'name.required' => 'Nama metode pembayaran wajib diisi.',
            'type.required' => 'Jenis metode pembayaran wajib dipilih.',
            'type.in' => 'Jenis metode pembayaran tidak valid.',
            'sort_order.required' => 'Urutan tampil wajib diisi.',
            'sort_order.integer' => 'Urutan tampil harus berupa bilangan bulat.',
            'sort_order.min' => 'Urutan tampil tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'type' => trim((string) $this->input('type')),
        ]);
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        return PaymentMethod::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
