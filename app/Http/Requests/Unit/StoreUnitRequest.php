<?php

namespace App\Http\Requests\Unit;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Unit::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('name') && $this->nameExists()) {
                    $validator->errors()->add('name', 'Nama satuan sudah digunakan.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.max' => 'Nama satuan maksimal 255 karakter.',
            'symbol.max' => 'Simbol maksimal 20 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $symbol = trim((string) $this->input('symbol'));

        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'symbol' => $symbol === '' ? null : $symbol,
        ]);
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        return Unit::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
