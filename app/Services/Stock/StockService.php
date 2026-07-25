<?php

namespace App\Services\Stock;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public const STATUS_SAFE = 'safe';

    public const STATUS_LOW = 'low';

    public const STATUS_OUT = 'out';

    public function setInitialStock(
        Branch $branch,
        Product $product,
        string $targetQuantity,
        string $reason,
        User $actor,
    ): BranchStock {
        return DB::transaction(function () use ($branch, $product, $targetQuantity, $reason, $actor): BranchStock {
            $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());

            if (! $lockedBranch->is_active) {
                throw ValidationException::withMessages(['branch_id' => 'Cabang tidak aktif.']);
            }

            if (! $lockedProduct->is_active) {
                throw ValidationException::withMessages(['product_id' => 'Produk tidak aktif.']);
            }

            $normalizedReason = trim($reason);

            if (mb_strlen($normalizedReason) < 5 || mb_strlen($normalizedReason) > 500) {
                throw ValidationException::withMessages([
                    'reason' => 'Alasan perubahan wajib diisi antara 5 sampai 500 karakter.',
                ]);
            }

            $targetUnits = $this->decimalToThousandths($targetQuantity);

            if ($targetUnits < 0) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah stok tidak boleh negatif.']);
            }

            if ($targetUnits > 0 && (float) $lockedProduct->purchase_price <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Harga modal referensi produk belum ditetapkan. Silakan minta Owner memperbarui harga beli produk terlebih dahulu.',
                ]);
            }

            $branchStock = $this->getOrCreateLockedBranchStock($lockedBranch, $lockedProduct);

            if ($this->hasOperationalMovement($lockedBranch, $lockedProduct)) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok awal tidak dapat diubah karena produk sudah memiliki aktivitas stok. Gunakan fitur penyesuaian stok pada tahap berikutnya.',
                ]);
            }

            $beforeUnits = $this->decimalToThousandths((string) $branchStock->quantity);
            $changeUnits = $targetUnits - $beforeUnits;

            if ($changeUnits === 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Tidak ada perubahan quantity. Riwayat stok tidak dibuat.',
                ]);
            }

            $quantityBefore = $this->thousandthsToDecimal($beforeUnits);
            $quantityChange = $this->thousandthsToDecimal($changeUnits);
            $quantityAfter = $this->thousandthsToDecimal($targetUnits);
            $unitCost = (string) $lockedProduct->purchase_price;

            $attributes = ['quantity' => $quantityAfter];

            if ((float) $branchStock->average_cost <= 0) {
                $attributes['average_cost'] = $unitCost;
            }

            $branchStock->update($attributes);

            StockMovement::query()->create([
                'branch_id' => $lockedBranch->getKey(),
                'product_id' => $lockedProduct->getKey(),
                'created_by' => $actor->getKey(),
                'movement_type' => StockMovement::TYPE_INITIAL,
                'reference_type' => null,
                'reference_id' => null,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'notes' => $normalizedReason,
            ]);

            return $branchStock->refresh();
        }, 3);
    }

    public function canSetInitialStock(Branch $branch, Product $product): bool
    {
        return $branch->is_active
            && $product->is_active
            && ! $this->hasOperationalMovement($branch, $product);
    }

    public function hasOperationalMovement(Branch $branch, Product $product): bool
    {
        return StockMovement::query()
            ->where('branch_id', $branch->getKey())
            ->where('product_id', $product->getKey())
            ->where('movement_type', '!=', StockMovement::TYPE_INITIAL)
            ->exists();
    }

    public function calculateStockStatus(string $quantity, string $minimumStock): string
    {
        $quantityUnits = $this->decimalToThousandths($quantity);
        $minimumUnits = $this->decimalToThousandths($minimumStock);

        if ($quantityUnits <= 0) {
            return self::STATUS_OUT;
        }

        return $quantityUnits <= $minimumUnits ? self::STATUS_LOW : self::STATUS_SAFE;
    }

    public function getOrCreateLockedBranchStock(Branch $branch, Product $product): BranchStock
    {
        BranchStock::query()->insertOrIgnore([
            'branch_id' => $branch->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => '0.000',
            'average_cost' => (string) $product->purchase_price,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return BranchStock::query()
            ->where('branch_id', $branch->getKey())
            ->where('product_id', $product->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function decimalToThousandths(string $value): int
    {
        $normalized = trim(str_replace(',', '.', $value));
        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '+-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = substr(str_pad($fraction, 3, '0'), 0, 3);
        $units = ((int) $whole * 1000) + (int) $fraction;

        return $negative ? -$units : $units;
    }

    private function thousandthsToDecimal(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $absolute = abs($value);

        return sprintf('%s%d.%03d', $sign, intdiv($absolute, 1000), $absolute % 1000);
    }
}
