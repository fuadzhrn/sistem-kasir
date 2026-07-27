<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use App\Support\Format\Quantity;
use App\Support\Format\Rupiah;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) === true;
    }

    public function rules(): array
    {
        return [
            'default_minimum_stock' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999.999'],
            'maximum_cashier_discount' => ['required', 'integer', 'min:0', 'max:9999999999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_minimum_stock.required' => 'Stok minimum default wajib diisi.',
            'default_minimum_stock.decimal' => 'Stok minimum maksimal menggunakan tiga angka desimal.',
            'default_minimum_stock.min' => 'Stok minimum tidak boleh negatif.',
            'maximum_cashier_discount.required' => 'Batas diskon Kasir wajib diisi.',
            'maximum_cashier_discount.integer' => 'Batas diskon tidak valid.',
            'maximum_cashier_discount.min' => 'Batas diskon tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_minimum_stock' => Quantity::normalizeInput(
                $this->input('default_minimum_stock'),
            ),
            'maximum_cashier_discount' => Rupiah::normalizeInput(
                $this->input('maximum_cashier_discount'),
            ),
        ]);
    }
}
