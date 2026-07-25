<?php

namespace App\Http\Requests\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && $this->user()?->can('update', $product) === true;
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        $rules = parent::rules();
        $rules['code'] = [
            'required',
            'string',
            'min:2',
            'max:40',
            'regex:/^[A-Z0-9_-]+$/',
            Rule::unique('products', 'code')->ignore($product),
        ];
        $rules['barcode'] = [
            'nullable',
            'string',
            'max:100',
            'not_regex:/[\x00-\x1F\x7F]/',
            Rule::unique('products', 'barcode')->ignore($product),
        ];
        $rules['price_change_reason'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Product $product */
                $product = $this->route('product');

                if (! $validator->errors()->has('code') && $this->codeExists((int) $product->getKey())) {
                    $validator->errors()->add('code', 'Kode produk sudah digunakan.');
                }

                if (! $validator->errors()->has('category_id')
                    && (int) $this->input('category_id') !== (int) $product->category_id
                    && ! Category::query()->whereKey($this->integer('category_id'))->where('is_active', true)->exists()) {
                    $validator->errors()->add('category_id', 'Kategori tujuan harus aktif.');
                }

                if (! $validator->errors()->has('unit_id')
                    && (int) $this->input('unit_id') !== (int) $product->unit_id
                    && ! Unit::query()->whereKey($this->integer('unit_id'))->where('is_active', true)->exists()) {
                    $validator->errors()->add('unit_id', 'Satuan tujuan harus aktif.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'price_change_reason' => $this->nullableTrimmed('price_change_reason'),
        ]);
    }
}
