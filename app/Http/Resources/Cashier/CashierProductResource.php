<?php

namespace App\Http\Resources\Cashier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CashierProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stockQuantity = $this->normalizeDecimal((string) $this->stock_quantity, 3);
        $minimumStock = $this->normalizeDecimal((string) $this->minimum_stock, 3);
        [$status, $statusLabel] = $this->stockStatus($stockQuantity, $minimumStock);

        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'barcode' => $this->barcode,
            'name' => (string) $this->name,
            'brand' => $this->brand,
            'size' => $this->size,
            'category_name' => (string) $this->category_name,
            'unit_name' => (string) $this->unit_name,
            'unit_symbol' => $this->unit_symbol,
            'selling_price' => $this->normalizeDecimal((string) $this->selling_price, 2),
            'stock_quantity' => $stockQuantity,
            'stock_status' => $status,
            'stock_status_label' => $statusLabel,
            'image_url' => $this->imageUrl(),
            'is_available' => $status !== 'out',
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function stockStatus(string $quantity, string $minimumStock): array
    {
        $quantityUnits = $this->scaledInteger($quantity, 3);
        $minimumUnits = $this->scaledInteger($minimumStock, 3);

        if ($quantityUnits <= 0) {
            return ['out', 'Habis'];
        }

        if ($quantityUnits <= $minimumUnits) {
            return ['low', 'Menipis'];
        }

        return ['safe', 'Tersedia'];
    }

    private function imageUrl(): string
    {
        $path = trim((string) $this->image_path);

        if ($path === '' || str_contains($path, '..') || str_contains($path, ':')) {
            return asset('assets/images/placeholders/product.svg');
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    private function normalizeDecimal(string $value, int $scale): string
    {
        $normalized = str_replace(',', '.', trim($value));
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.substr(str_pad($fraction, $scale, '0'), 0, $scale);
    }

    private function scaledInteger(string $value, int $scale): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * (10 ** $scale))
            + (int) substr(str_pad($fraction, $scale, '0'), 0, $scale);
    }
}
