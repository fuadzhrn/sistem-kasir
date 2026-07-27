<?php

namespace App\Services\Receipt;

use App\Models\Sale;
use App\Models\Setting;
use App\Services\Setting\StoreLogoService;
use App\Services\Setting\StoreSettingService;

class ReceiptViewDataService
{
    public function __construct(
        private readonly StoreSettingService $settings,
        private readonly StoreLogoService $logos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Sale $sale): array
    {
        $legacyReceipt = Setting::query()
            ->whereIn('key', ['store_name', 'receipt_message'])
            ->exists();
        $productCodeConfigured = $this->settings->hasStored('receipt.show_product_code');
        $notesConfigured = $this->settings->hasStored('receipt.show_transaction_notes');
        $storeName = $this->settings->storeName();
        $storeAddress = $this->nullableText($this->settings->get('store.address'));
        $storePhone = $this->nullableText($this->settings->get('store.phone'));
        $showBranchAddress = (bool) $this->settings->get('receipt.show_branch_address');
        $showBranchPhone = (bool) $this->settings->get('receipt.show_branch_phone');
        $branchAddress = $showBranchAddress
            ? $this->nullableText($sale->branch?->address)
            : null;
        $branchPhone = $showBranchPhone
            ? $this->nullableText($sale->branch?->phone)
            : null;
        $address = $branchAddress ?? (
            (bool) $this->settings->get('receipt.show_store_address') ? $storeAddress : null
        );
        $phone = $branchPhone ?? (
            (bool) $this->settings->get('receipt.show_store_phone') ? $storePhone : null
        );
        $closingMessage = $this->fallbackText(
            $this->settings->get('receipt.footer_message'),
            'Terima kasih telah berbelanja.',
        );

        return [
            'store_name' => $storeName,
            'store_logo_url' => (bool) $this->settings->get('receipt.show_logo')
                ? $this->logos->logoUrl()
                : null,
            'store_address' => $storeAddress,
            'store_phone' => $storePhone,
            'branch_name' => $sale->branch?->name ?? 'Cabang',
            'branch_address' => $address,
            'branch_phone' => $phone,
            'invoice_number' => $sale->invoice_number,
            'transaction_date' => $sale->transaction_date,
            'cashier_name' => $sale->cashier?->name ?? 'Pengguna historis',
            'status' => $sale->status,
            'status_label' => $sale->statusLabel(),
            'void_reason' => $sale->isVoided()
                ? $this->nullableText($sale->saleVoid?->reason)
                : null,
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
            'additional_information' => $this->nullableText(
                $this->settings->get('receipt.additional_information'),
            ),
            'show_product_code' => $legacyReceipt || ! $productCodeConfigured
                || (bool) $this->settings->get('receipt.show_product_code'),
            'show_transaction_notes' => $legacyReceipt || ! $notesConfigured
                || (bool) $this->settings->get('receipt.show_transaction_notes'),
            'show_copy_label' => (bool) $this->settings->get('receipt.show_copy_label'),
            'default_paper_width' => $this->settings->defaultPaperWidth(),
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
