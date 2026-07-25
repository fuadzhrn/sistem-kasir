<?php

namespace App\Http\Requests\Cashier;

use App\Models\Sale;
use App\Support\Format\Rupiah;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreCashierCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->is_active
            && $user->can('create', Sale::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isOwner = $this->user()?->isOwner() === true;

        return [
            'checkout_token' => [
                'required',
                'string',
                'min:16',
                'max:64',
                'regex:/\A[A-Za-z0-9_-]+\z/',
            ],
            'branch_id' => [
                $isOwner ? 'required' : 'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('products', 'id'),
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999999.999',
                'regex:/\A\d+(?:\.\d{1,3})?\z/',
            ],
            'discount_amount' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999999999999999',
            ],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'amount_received' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999999999999999',
            ],
            'payment_action' => [
                'required',
                Rule::in(['print', 'no_print']),
            ],
            'expected_subtotal' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999999.99',
                'decimal:0,2',
            ],
            'expected_total' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999999.99',
                'decimal:0,2',
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'checkout_token.required' => 'Token checkout wajib tersedia.',
            'checkout_token.min' => 'Token checkout tidak valid.',
            'checkout_token.max' => 'Token checkout maksimal 64 karakter.',
            'checkout_token.regex' => 'Token checkout mengandung karakter yang tidak diizinkan.',
            'branch_id.required' => 'Owner wajib memilih cabang.',
            'branch_id.exists' => 'Cabang tidak tersedia atau tidak aktif.',
            'items.required' => 'Keranjang wajib berisi produk.',
            'items.min' => 'Keranjang wajib berisi minimal satu produk.',
            'items.max' => 'Maksimal 100 jenis produk dalam satu transaksi.',
            'items.*.array' => 'Format item keranjang tidak valid.',
            'items.*.product_id.required' => 'Produk pada keranjang wajib dipilih.',
            'items.*.product_id.distinct' => 'Produk duplikat tidak diperbolehkan.',
            'items.*.product_id.exists' => 'Salah satu produk tidak tersedia.',
            'items.*.quantity.required' => 'Quantity produk wajib diisi.',
            'items.*.quantity.gt' => 'Quantity produk harus lebih besar dari nol.',
            'items.*.quantity.regex' => 'Quantity maksimal menggunakan tiga angka desimal.',
            'discount_amount.min' => 'Diskon tidak boleh negatif.',
            'discount_amount.integer' => 'Diskon harus berupa Rupiah tanpa desimal.',
            'payment_method_id.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method_id.exists' => 'Metode pembayaran tidak tersedia atau tidak aktif.',
            'amount_received.min' => 'Uang diterima tidak boleh negatif.',
            'amount_received.integer' => 'Uang diterima harus berupa Rupiah tanpa desimal.',
            'payment_action.required' => 'Tindakan pembayaran wajib dipilih.',
            'payment_action.in' => 'Tindakan pembayaran tidak valid.',
            'expected_subtotal.decimal' => 'Subtotal keranjang tidak valid.',
            'expected_total.decimal' => 'Total keranjang tidak valid.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $notes = trim((string) $this->input('notes'));

        $this->merge([
            'discount_amount' => Rupiah::normalizeInput($this->input('discount_amount')),
            'amount_received' => Rupiah::normalizeInput($this->input('amount_received')),
            'expected_subtotal' => Rupiah::normalizeInput($this->input('expected_subtotal')),
            'expected_total' => Rupiah::normalizeInput($this->input('expected_total')),
            'notes' => $notes === '' ? null : $notes,
        ]);
    }

    protected function failedValidation(Validator $validator): never
    {
        $errors = $validator->errors();
        $code = 'VALIDATION_FAILED';

        if ($errors->has('branch_id')) {
            $code = $this->user()?->isOwner() ? 'BRANCH_INACTIVE' : 'BRANCH_NOT_ALLOWED';
        } elseif ($errors->has('payment_method_id')) {
            $code = 'PAYMENT_METHOD_INACTIVE';
        } elseif ($errors->has('discount_amount')) {
            $code = 'INVALID_DISCOUNT';
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'code' => $code,
            'message' => $errors->first(),
            'errors' => $errors->toArray(),
        ], 422));
    }
}
