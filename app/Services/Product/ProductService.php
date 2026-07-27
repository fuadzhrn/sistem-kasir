<?php

namespace App\Services\Product;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use App\Services\Setting\StoreSettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductService
{
    public function __construct(private readonly StoreSettingService $settings) {}

    public function create(array $data, User $actor, ?UploadedFile $image = null): Product
    {
        $newImagePath = $this->storeImage($image);

        try {
            return DB::transaction(fn (): Product => Product::query()->create([
                'category_id' => $data['category_id'],
                'unit_id' => $data['unit_id'],
                'code' => $data['code'],
                'barcode' => $data['barcode'] ?? null,
                'name' => $data['name'],
                'brand' => $data['brand'] ?? null,
                'size' => $data['size'] ?? null,
                'purchase_price' => $actor->isOwner() ? $data['purchase_price'] : '0.00',
                'selling_price' => $data['selling_price'],
                'minimum_stock' => $data['minimum_stock'] ?? $this->settings->defaultMinimumStock(),
                'image_path' => $newImagePath,
                'is_active' => true,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]));
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }
    }

    public function update(
        Product $product,
        array $data,
        User $actor,
        ?UploadedFile $image = null,
    ): Product {
        $newImagePath = $this->storeImage($image);
        $oldImagePath = $product->image_path;

        try {
            $updated = DB::transaction(function () use ($product, $data, $actor, $newImagePath): Product {
                $locked = Product::query()->lockForUpdate()->findOrFail($product->getKey());
                $oldPurchasePrice = (string) $locked->purchase_price;
                $oldSellingPrice = (string) $locked->selling_price;
                $newPurchasePrice = $actor->isOwner()
                    ? (string) $data['purchase_price']
                    : $oldPurchasePrice;
                $newSellingPrice = (string) $data['selling_price'];

                $attributes = [
                    'category_id' => $data['category_id'],
                    'unit_id' => $data['unit_id'],
                    'code' => $data['code'],
                    'barcode' => $data['barcode'] ?? null,
                    'name' => $data['name'],
                    'brand' => $data['brand'] ?? null,
                    'size' => $data['size'] ?? null,
                    'purchase_price' => $newPurchasePrice,
                    'selling_price' => $newSellingPrice,
                    'minimum_stock' => $data['minimum_stock'],
                    'updated_by' => $actor->getKey(),
                ];

                if ($newImagePath !== null) {
                    $attributes['image_path'] = $newImagePath;
                }

                $locked->update($attributes);

                if ($this->hasPriceChanged(
                    $oldPurchasePrice,
                    $newPurchasePrice,
                    $oldSellingPrice,
                    $newSellingPrice,
                )) {
                    $this->recordPriceHistory(
                        $locked,
                        $actor,
                        $oldPurchasePrice,
                        $newPurchasePrice,
                        $oldSellingPrice,
                        $newSellingPrice,
                        $data['price_change_reason'] ?? null,
                    );
                }

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath !== null && $this->isProductImagePath($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $updated;
    }

    public function updateStatus(Product $product, bool $isActive, User $actor): Product
    {
        return DB::transaction(function () use ($product, $isActive, $actor): Product {
            $locked = Product::query()
                ->with(['category:id,is_active', 'unit:id,is_active'])
                ->lockForUpdate()
                ->findOrFail($product->getKey());

            if ($isActive && ! $locked->category?->is_active) {
                throw ValidationException::withMessages([
                    'is_active' => 'Produk tidak dapat diaktifkan karena kategori tidak aktif.',
                ]);
            }

            if ($isActive && ! $locked->unit?->is_active) {
                throw ValidationException::withMessages([
                    'is_active' => 'Produk tidak dapat diaktifkan karena satuan tidak aktif.',
                ]);
            }

            $locked->update([
                'is_active' => $isActive,
                'updated_by' => $actor->getKey(),
            ]);

            return $locked->refresh();
        });
    }

    public function removeImage(Product $product, User $actor): Product
    {
        $oldImagePath = $product->image_path;
        $updated = DB::transaction(function () use ($product, $actor): Product {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $locked->update([
                'image_path' => null,
                'updated_by' => $actor->getKey(),
            ]);

            return $locked->refresh();
        });

        if ($this->isProductImagePath($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $updated;
    }

    public function recordPriceHistory(
        Product $product,
        User $actor,
        string $oldPurchasePrice,
        string $newPurchasePrice,
        string $oldSellingPrice,
        string $newSellingPrice,
        ?string $reason,
    ): void {
        PriceHistory::query()->create([
            'product_id' => $product->getKey(),
            'changed_by' => $actor->getKey(),
            'old_purchase_price' => $oldPurchasePrice,
            'new_purchase_price' => $newPurchasePrice,
            'old_selling_price' => $oldSellingPrice,
            'new_selling_price' => $newSellingPrice,
            'reason' => $reason,
            'changed_at' => now(),
        ]);
    }

    public function hasPriceChanged(
        string $oldPurchasePrice,
        string $newPurchasePrice,
        string $oldSellingPrice,
        string $newSellingPrice,
    ): bool {
        return $this->normalizeDecimal($oldPurchasePrice, 2) !== $this->normalizeDecimal($newPurchasePrice, 2)
            || $this->normalizeDecimal($oldSellingPrice, 2) !== $this->normalizeDecimal($newSellingPrice, 2);
    }

    private function normalizeDecimal(string $value, int $scale): string
    {
        [$whole, $fraction] = array_pad(explode('.', ltrim(trim($value), '+'), 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);

        return "{$whole}.{$fraction}";
    }

    private function isProductImagePath(?string $path): bool
    {
        return $path !== null
            && str_starts_with($path, 'products/')
            && ! str_contains($path, '..');
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $path = $image->store('products', 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'image' => 'Foto produk tidak dapat disimpan. Periksa izin folder storage.',
            ]);
        }

        return $path;
    }
}
