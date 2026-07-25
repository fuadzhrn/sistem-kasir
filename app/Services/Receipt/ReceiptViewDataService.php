<?php

namespace App\Services\Receipt;

use App\Models\Sale;
use App\Models\Setting;

class ReceiptViewDataService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Sale $sale): array
    {
        $settings = Setting::query()
            ->whereIn('key', ['store_name', 'receipt_message'])
            ->pluck('value', 'key');
        $storeName = $this->fallbackText(
            $settings->get('store_name'),
            config('receipt.store_name'),
            config('app.name'),
        );
        $closingMessage = $this->fallbackText(
            $settings->get('receipt_message'),
            config('receipt.closing_message'),
            'Terima kasih telah berbelanja.',
        );

        return [
            'store_name' => $storeName,
            'branch_name' => $sale->branch?->name ?? 'Cabang Toko',
            'branch_address' => $this->nullableText($sale->branch?->address),
            'branch_phone' => $this->nullableText($sale->branch?->phone),
            'invoice_number' => $sale->invoice_number,
            'transaction_date' => $sale->transaction_date,
            'cashier_name' => $sale->cashier?->name ?? 'Pengguna historis',
            'status' => $sale->status,
            'status_label' => $sale->statusLabel(),
            'items' => $sale->items->map(static fn ($item): array => [
                'code' => $item->product_code,
                'name' => $item->product_name,
                'size' => $item->product_size,
                'unit_name' => $item->unit_name,
                'quantity' => $item->quantity,
                'selling_price' => $item->selling_price,
                'discount_amount' => $item->discount_amount,
                'subtotal' => $item->subtotal,
            ])->all(),
            'subtotal' => $sale->subtotal,
            'discount_amount' => $sale->discount_amount,
            'total' => $sale->total,
            'payment_method_name' => $sale->payment_method_name,
            'amount_paid' => $sale->amount_paid,
            'change_amount' => $sale->change_amount,
            'notes' => $this->nullableText($sale->notes),
            'closing_message' => $closingMessage,
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function fallbackText(mixed ...$values): string
    {
        foreach ($values as $value) {
            $normalized = $this->nullableText($value);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return '';
    }
}
