<?php

namespace App\Services\StockAdjustment;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;
use App\Services\Calculation\QuantityCalculator;
use App\Services\Calculation\WeightedAverageCostCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(
        private readonly StockAdjustmentNumberService $numberService,
        private readonly QuantityCalculator $quantityCalculator,
        private readonly WeightedAverageCostCalculator $moneyCalculator,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function create(
        Branch $branch,
        Product $product,
        string $adjustmentType,
        ?string $quantity,
        ?string $targetQuantity,
        string $reason,
        User $actor,
    ): StockAdjustment {
        if (
            (! $actor->isOwner() && ! $actor->isAdmin())
            || ! $this->branchAccess->canAccessBranch($actor, $branch)
        ) {
            throw new AuthorizationException('Anda tidak memiliki akses ke cabang tersebut.');
        }

        if (! in_array($adjustmentType, StockAdjustment::types(), true)) {
            throw ValidationException::withMessages(['adjustment_type' => 'Jenis penyesuaian tidak valid.']);
        }

        $normalizedReason = $this->normalizeReason($reason);
        $normalizedQuantity = $adjustmentType === StockAdjustment::TYPE_CORRECTION
            ? null
            : $this->quantityCalculator->normalize((string) $quantity);
        $normalizedTarget = $adjustmentType === StockAdjustment::TYPE_CORRECTION
            ? $this->quantityCalculator->normalize((string) $targetQuantity)
            : null;

        if (
            $normalizedQuantity !== null
            && $this->quantityCalculator->compare($normalizedQuantity, '0') <= 0
        ) {
            throw ValidationException::withMessages(['quantity' => 'Quantity harus lebih besar dari nol.']);
        }

        if (
            $normalizedTarget !== null
            && $this->quantityCalculator->compare($normalizedTarget, '0') < 0
        ) {
            throw ValidationException::withMessages(['target_quantity' => 'Target quantity tidak boleh negatif.']);
        }

        return DB::transaction(function () use (
            $branch,
            $product,
            $adjustmentType,
            $normalizedQuantity,
            $normalizedTarget,
            $normalizedReason,
            $actor,
        ): StockAdjustment {
            $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());

            if (! $lockedBranch->is_active) {
                throw ValidationException::withMessages(['branch_id' => 'Cabang tidak aktif.']);
            }

            if (! $lockedProduct->is_active) {
                throw ValidationException::withMessages(['product_id' => 'Produk tidak aktif.']);
            }

            $branchStock = $this->resolveLockedStock(
                $lockedBranch,
                $lockedProduct,
                $adjustmentType,
            );
            $quantityBefore = $this->quantityCalculator->normalize((string) $branchStock->quantity);
            $quantityChange = $this->calculateChange(
                $adjustmentType,
                $quantityBefore,
                $normalizedQuantity,
                $normalizedTarget,
            );

            if ($this->quantityCalculator->compare($quantityChange, '0') === 0) {
                throw ValidationException::withMessages([
                    'target_quantity' => 'Koreksi ditolak karena tidak menghasilkan perubahan stok.',
                ]);
            }

            $quantityAfter = $this->quantityCalculator->add($quantityBefore, $quantityChange);

            if ($this->quantityCalculator->compare($quantityAfter, '0') < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak mencukupi untuk penyesuaian ini.',
                ]);
            }

            $movementType = $this->quantityCalculator->compare($quantityChange, '0') > 0
                ? StockMovement::TYPE_ADJUSTMENT_IN
                : StockMovement::TYPE_ADJUSTMENT_OUT;
            $unitCost = $this->resolveUnitCost(
                $branchStock,
                $lockedProduct,
                $movementType,
            );
            $adjustment = StockAdjustment::query()->create([
                'adjustment_number' => $this->numberService->generate($lockedBranch, now()),
                'branch_id' => $lockedBranch->getKey(),
                'product_id' => $lockedProduct->getKey(),
                'adjustment_type' => $adjustmentType,
                'quantity' => $adjustmentType === StockAdjustment::TYPE_CORRECTION
                    ? $this->quantityCalculator->absolute($quantityChange)
                    : $normalizedQuantity,
                'target_quantity' => $normalizedTarget,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'reason' => $normalizedReason,
                'created_by' => $actor->getKey(),
            ]);

            $branchStock->update(['quantity' => $quantityAfter]);

            StockMovement::query()->create([
                'branch_id' => $lockedBranch->getKey(),
                'product_id' => $lockedProduct->getKey(),
                'created_by' => $actor->getKey(),
                'movement_type' => $movementType,
                'reference_type' => StockAdjustment::class,
                'reference_id' => $adjustment->getKey(),
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'notes' => $this->buildMovementNotes($adjustment),
            ]);

            return $adjustment->refresh();
        }, 3);
    }

    private function resolveLockedStock(
        Branch $branch,
        Product $product,
        string $adjustmentType,
    ): BranchStock {
        if (
            $adjustmentType === StockAdjustment::TYPE_ADDITION
            || $adjustmentType === StockAdjustment::TYPE_CORRECTION
        ) {
            BranchStock::query()->insertOrIgnore([
                'branch_id' => $branch->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => '0.000',
                'average_cost' => '0.00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $branchStock = BranchStock::query()
            ->where('branch_id', $branch->getKey())
            ->where('product_id', $product->getKey())
            ->lockForUpdate()
            ->first();

        if ($branchStock === null) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok produk pada cabang belum tersedia.',
            ]);
        }

        return $branchStock;
    }

    private function calculateChange(
        string $adjustmentType,
        string $quantityBefore,
        ?string $quantity,
        ?string $targetQuantity,
    ): string {
        if ($adjustmentType === StockAdjustment::TYPE_ADDITION) {
            return (string) $quantity;
        }

        if ($adjustmentType === StockAdjustment::TYPE_CORRECTION) {
            return $this->quantityCalculator->subtract((string) $targetQuantity, $quantityBefore);
        }

        return $this->quantityCalculator->negate((string) $quantity);
    }

    private function resolveUnitCost(
        BranchStock $branchStock,
        Product $product,
        string $movementType,
    ): string {
        $unitCost = $this->moneyCalculator->normalizeMoney((string) $branchStock->average_cost);

        if ($movementType === StockMovement::TYPE_ADJUSTMENT_IN && $unitCost === '0.00') {
            $unitCost = $this->moneyCalculator->normalizeMoney((string) $product->purchase_price);

            if ($unitCost === '0.00') {
                throw ValidationException::withMessages([
                    'quantity' => 'Harga modal referensi belum tersedia. Owner harus menetapkan harga beli produk terlebih dahulu.',
                ]);
            }
        }

        return $unitCost;
    }

    private function normalizeReason(string $reason): string
    {
        $normalized = trim($reason);

        if (mb_strlen($normalized) < 10 || mb_strlen($normalized) > 1000) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan wajib berisi 10 sampai 1000 karakter.',
            ]);
        }

        if (in_array(mb_strtolower($normalized), ['test', 'ubah', 'stok', 'salah'], true)) {
            throw ValidationException::withMessages(['reason' => 'Gunakan alasan yang spesifik dan dapat diaudit.']);
        }

        return $normalized;
    }

    private function buildMovementNotes(StockAdjustment $adjustment): string
    {
        return $adjustment->adjustment_number.' - '.$adjustment->type_label.' - '.$adjustment->reason;
    }
}
