<?php

namespace App\Http\Requests\StockReceipt;

use App\Models\StockReceipt;
use App\Support\Format\Quantity;
use App\Support\Format\Rupiah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockReceipt::class) === true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                $this->user()?->isOwner() ? 'required' : 'prohibited',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'receipt_date' => ['required', 'date', 'before_or_equal:today'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => [
                'required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999',
            ],
            'items.*.purchase_price' => [
                'required', 'integer', 'gt:0', 'max:999999999',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.prohibited' => 'Cabang Admin ditentukan dari akun dan tidak boleh dikirim.',
            'branch_id.exists' => 'Cabang aktif yang dipilih tidak tersedia.',
            'receipt_date.required' => 'Tanggal penerimaan wajib diisi.',
            'receipt_date.date' => 'Tanggal penerimaan tidak valid.',
            'receipt_date.before_or_equal' => 'Tanggal penerimaan tidak boleh melewati hari ini.',
            'supplier_name.max' => 'Nama supplier maksimal 255 karakter.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
            'items.required' => 'Minimal satu produk wajib ditambahkan.',
            'items.min' => 'Minimal satu produk wajib ditambahkan.',
            'items.max' => 'Maksimal 100 produk dalam satu penerimaan.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.distinct' => 'Produk duplikat ditemukan.',
            'items.*.product_id.exists' => 'Produk harus tersedia dan aktif.',
            'items.*.quantity.required' => 'Quantity wajib diisi.',
            'items.*.quantity.decimal' => 'Quantity maksimal menggunakan tiga angka desimal.',
            'items.*.quantity.gt' => 'Quantity harus lebih besar dari nol.',
            'items.*.purchase_price.required' => 'Harga beli wajib diisi.',
            'items.*.purchase_price.integer' => 'Harga beli harus berupa Rupiah tanpa desimal.',
            'items.*.purchase_price.gt' => 'Harga beli harus lebih besar dari nol.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_array($items)) {
            $items = array_map(static function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                if (array_key_exists('quantity', $item)) {
                    $item['quantity'] = Quantity::normalizeInput($item['quantity']);
                }

                if (array_key_exists('purchase_price', $item)) {
                    $item['purchase_price'] = Rupiah::normalizeInput($item['purchase_price']);
                }

                return $item;
            }, $items);
        }

        $this->merge([
            'supplier_name' => $this->nullableTrimmed($this->input('supplier_name')),
            'notes' => $this->nullableTrimmed($this->input('notes')),
            'items' => $items,
        ]);
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
