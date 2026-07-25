<?php

namespace App\Http\Requests\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) === true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'code' => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Z0-9_-]+$/', 'unique:products,code'],
            'barcode' => ['nullable', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:150'],
            'size' => ['nullable', 'string', 'max:100'],
            'purchase_price' => $this->purchasePriceRules(),
            'selling_price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'decimal:0,3', 'min:0'],
            'image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(3 * 1024),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('code') && $this->codeExists()) {
                    $validator->errors()->add('code', 'Kode produk sudah digunakan.');
                }

                if (! $validator->errors()->has('category_id')
                    && ! Category::query()->whereKey($this->integer('category_id'))->where('is_active', true)->exists()) {
                    $validator->errors()->add('category_id', 'Kategori yang dipilih harus aktif.');
                }

                if (! $validator->errors()->has('unit_id')
                    && ! Unit::query()->whereKey($this->integer('unit_id'))->where('is_active', true)->exists()) {
                    $validator->errors()->add('unit_id', 'Satuan yang dipilih harus aktif.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'unit_id.required' => 'Satuan wajib dipilih.',
            'unit_id.exists' => 'Satuan tidak valid.',
            'code.required' => 'Kode produk wajib diisi.',
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah.',
            'code.unique' => 'Kode produk sudah digunakan.',
            'barcode.unique' => 'Barcode sudah digunakan.',
            'barcode.not_regex' => 'Barcode mengandung karakter yang tidak diperbolehkan.',
            'name.required' => 'Nama produk wajib diisi.',
            'purchase_price.prohibited' => 'Anda tidak memiliki izin mengubah harga beli.',
            'purchase_price.required' => 'Harga beli wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'minimum_stock.required' => 'Stok minimum wajib diisi.',
            'minimum_stock.min' => 'Stok minimum tidak boleh negatif.',
            'image.image' => 'Format foto tidak didukung.',
            'image.max' => 'Ukuran foto maksimal 3 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'barcode' => $this->nullableTrimmed('barcode'),
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'brand' => $this->nullableTrimmed('brand'),
            'size' => $this->nullableTrimmed('size'),
        ]);
    }

    protected function purchasePriceRules(): array
    {
        if ($this->user()?->isOwner()) {
            return ['required', 'numeric', 'decimal:0,2', 'min:0'];
        }

        return ['prohibited'];
    }

    protected function codeExists(?int $ignoreId = null): bool
    {
        return Product::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower((string) $this->input('code'))])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    protected function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
