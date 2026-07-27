<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) === true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'min:2', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_name.required' => 'Nama toko wajib diisi.',
            'store_name.min' => 'Nama toko minimal 2 karakter.',
            'store_name.max' => 'Nama toko maksimal 150 karakter.',
            'store_address.max' => 'Alamat toko maksimal 1.000 karakter.',
            'store_phone.regex' => 'Format nomor telepon tidak valid.',
            'store_phone.max' => 'Nomor telepon maksimal 30 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'store_name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('store_name'))),
            'store_address' => $this->nullableTrimmed('store_address'),
            'store_phone' => $this->nullableTrimmed('store_phone'),
        ]);
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
