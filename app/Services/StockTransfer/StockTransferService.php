<?php

namespace App\Services\StockTransfer;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Calculation\QuantityCalculator;
use App\Services\Calculation\WeightedAverageCostCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(
        private readonly StockTransferNumberService $numberService,
        private readonly QuantityCalculator $quantityCalculator,
        private readonly WeightedAverageCostCalculator $costCalculator,
    ) {}

    public function request(
        Branch $source,
        Branch $destination,
        Product $product,
        string $quantity,
        string $notes,
        User $actor,
    ): StockTransfer {
        $this->authorizeRequest($source, $actor);
        $normalizedQuantity = $this->quantityCalculator->normalize($quantity);
        $normalizedNotes = $this->normalizeText($notes, 'notes', 'Catatan');

        if ($this->quantityCalculator->compare($normalizedQuantity, '0') <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity mutasi harus lebih besar dari nol.',
            ]);
        }

        if ($source->is($destination)) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'Cabang tujuan harus berbeda dari cabang asal.',
            ]);
        }

        return DB::transaction(function () use (
            $source,
            $destination,
            $product,
            $normalizedQuantity,
            $normalizedNotes,
            $actor,
        ): StockTransfer {
            $branches = $this->lockBranches($source->getKey(), $destination->getKey());
            $lockedSource = $branches->get($source->getKey());
            $lockedDestination = $branches->get($destination->getKey());
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());

            if (! $lockedSource->is_active) {
                throw ValidationException::withMessages(['from_branch_id' => 'Cabang asal tidak aktif.']);
            }

            if (! $lockedDestination->is_active) {
                throw ValidationException::withMessages(['to_branch_id' => 'Cabang tujuan tidak aktif.']);
            }

            if (! $lockedProduct->is_active) {
                throw ValidationException::withMessages(['product_id' => 'Produk tidak aktif.']);
            }

            return StockTransfer::query()->create([
                'transfer_number' => $this->numberService->generate(
                    $lockedSource,
                    $lockedDestination,
                    now(),
                ),
                'from_branch_id' => $lockedSource->getKey(),
                'to_branch_id' => $lockedDestination->getKey(),
                'product_id' => $lockedProduct->getKey(),
                'quantity' => $normalizedQuantity,
                'status' => StockTransfer::STATUS_PENDING,
                'unit_cost' => '0.00',
                'notes' => $normalizedNotes,
                'requested_by' => $actor->getKey(),
            ])->refresh();
        }, 3);
    }

    public function complete(StockTransfer $stockTransfer, User $actor): StockTransfer
    {
        $this->authorizeOwner($actor);

        return DB::transaction(function () use ($stockTransfer, $actor): StockTransfer {
            $lockedTransfer = StockTransfer::query()
                ->lockForUpdate()
                ->findOrFail($stockTransfer->getKey());
            $this->ensurePending($lockedTransfer);
            $branches = $this->lockBranches(
                $lockedTransfer->from_branch_id,
                $lockedTransfer->to_branch_id,
            );
            $source = $branches->get($lockedTransfer->from_branch_id);
            $destination = $branches->get($lockedTransfer->to_branch_id);
            $product = Product::query()->lockForUpdate()->findOrFail($lockedTransfer->product_id);

            if (! $source->is_active || ! $destination->is_active) {
                throw ValidationException::withMessages([
                    'status' => 'Mutasi tidak dapat diselesaikan karena salah satu cabang tidak aktif.',
                ]);
            }

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'status' => 'Mutasi tidak dapat diselesaikan karena produk tidak aktif.',
                ]);
            }

            BranchStock::query()->insertOrIgnore([
                'branch_id' => $destination->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => '0.000',
                'average_cost' => '0.00',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stocks = BranchStock::query()
                ->where('product_id', $product->getKey())
                ->whereIn('branch_id', [$source->getKey(), $destination->getKey()])
                ->orderBy('branch_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('branch_id');
            $sourceStock = $stocks->get($source->getKey());
            $destinationStock = $stocks->get($destination->getKey());

            if ($sourceStock === null) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok produk pada cabang asal belum tersedia.',
                ]);
            }

            $quantity = $this->quantityCalculator->normalize((string) $lockedTransfer->quantity);
            $sourceBefore = $this->quantityCalculator->normalize((string) $sourceStock->quantity);

            if ($this->quantityCalculator->compare($sourceBefore, $quantity) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok cabang asal tidak mencukupi untuk menyelesaikan mutasi.',
                ]);
            }

            $unitCost = $this->costCalculator->normalizeMoney((string) $sourceStock->average_cost);

            if ($unitCost === '0.00') {
                throw ValidationException::withMessages([
                    'status' => 'Average cost cabang asal belum tersedia.',
                ]);
            }

            $sourceAfter = $this->quantityCalculator->subtract($sourceBefore, $quantity);
            $destinationBefore = $this->quantityCalculator->normalize((string) $destinationStock->quantity);
            $destinationAfter = $this->quantityCalculator->add($destinationBefore, $quantity);
            $destinationCostBefore = $this->costCalculator->normalizeMoney(
                (string) $destinationStock->average_cost,
            );
            $destinationCostAfter = $this->costCalculator->calculateWeightedAverage(
                $destinationBefore,
                $destinationCostBefore,
                $quantity,
                $unitCost,
            );

            $sourceStock->update(['quantity' => $sourceAfter]);
            $destinationStock->update([
                'quantity' => $destinationAfter,
                'average_cost' => $destinationCostAfter,
            ]);

            $movementNotes = 'Mutasi '.$lockedTransfer->transfer_number;
            StockMovement::query()->create([
                'branch_id' => $source->getKey(),
                'product_id' => $product->getKey(),
                'created_by' => $actor->getKey(),
                'movement_type' => StockMovement::TYPE_TRANSFER_OUT,
                'reference_type' => StockTransfer::class,
                'reference_id' => $lockedTransfer->getKey(),
                'quantity_before' => $sourceBefore,
                'quantity_change' => $this->quantityCalculator->negate($quantity),
                'quantity_after' => $sourceAfter,
                'unit_cost' => $unitCost,
                'notes' => $movementNotes.' keluar dari '.$source->name,
            ]);
            StockMovement::query()->create([
                'branch_id' => $destination->getKey(),
                'product_id' => $product->getKey(),
                'created_by' => $actor->getKey(),
                'movement_type' => StockMovement::TYPE_TRANSFER_IN,
                'reference_type' => StockTransfer::class,
                'reference_id' => $lockedTransfer->getKey(),
                'quantity_before' => $destinationBefore,
                'quantity_change' => $quantity,
                'quantity_after' => $destinationAfter,
                'unit_cost' => $unitCost,
                'notes' => $movementNotes.' masuk ke '.$destination->name,
            ]);

            $lockedTransfer->update([
                'status' => StockTransfer::STATUS_COMPLETED,
                'unit_cost' => $unitCost,
                'source_quantity_before' => $sourceBefore,
                'source_quantity_after' => $sourceAfter,
                'destination_quantity_before' => $destinationBefore,
                'destination_quantity_after' => $destinationAfter,
                'destination_average_cost_before' => $destinationCostBefore,
                'destination_average_cost_after' => $destinationCostAfter,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
                'completed_at' => now(),
            ]);

            return $lockedTransfer->refresh();
        }, 3);
    }

    public function reject(StockTransfer $stockTransfer, string $reason, User $actor): StockTransfer
    {
        $this->authorizeOwner($actor);

        return $this->closeWithoutStock(
            $stockTransfer,
            StockTransfer::STATUS_REJECTED,
            $this->normalizeText($reason, 'rejection_reason', 'Alasan penolakan'),
            $actor,
        );
    }

    public function cancel(
        StockTransfer $stockTransfer,
        ?string $reason,
        User $actor,
    ): StockTransfer {
        if (
            ! $actor->is_active
            || (
                ! $actor->isOwner()
                && (
                    ! $actor->isAdmin()
                    || $actor->branch_id !== $stockTransfer->from_branch_id
                    || $actor->getKey() !== $stockTransfer->requested_by
                )
            )
        ) {
            throw new AuthorizationException('Anda tidak dapat membatalkan mutasi ini.');
        }

        $normalizedReason = trim((string) $reason);

        if ($normalizedReason !== '' && (mb_strlen($normalizedReason) < 5 || mb_strlen($normalizedReason) > 1000)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'Alasan pembatalan harus berisi 5 sampai 1000 karakter.',
            ]);
        }

        return $this->closeWithoutStock(
            $stockTransfer,
            StockTransfer::STATUS_CANCELLED,
            $normalizedReason === '' ? null : $normalizedReason,
            $actor,
        );
    }

    private function closeWithoutStock(
        StockTransfer $stockTransfer,
        string $status,
        ?string $reason,
        User $actor,
    ): StockTransfer {
        return DB::transaction(function () use (
            $stockTransfer,
            $status,
            $reason,
            $actor,
        ): StockTransfer {
            $lockedTransfer = StockTransfer::query()
                ->lockForUpdate()
                ->findOrFail($stockTransfer->getKey());
            $this->ensurePending($lockedTransfer);
            $lockedTransfer->update([
                'status' => $status,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $lockedTransfer->refresh();
        }, 3);
    }

    private function lockBranches(int $sourceId, int $destinationId): Collection
    {
        $branches = Branch::query()
            ->whereIn('id', [$sourceId, $destinationId])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($branches->count() !== 2) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'Cabang asal atau tujuan tidak tersedia.',
            ]);
        }

        return $branches;
    }

    private function ensurePending(StockTransfer $stockTransfer): void
    {
        if (! $stockTransfer->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Mutasi sudah diproses dan tidak dapat diubah.',
            ]);
        }
    }

    private function authorizeRequest(Branch $source, User $actor): void
    {
        if (
            ! $actor->is_active
            || (
                ! $actor->isOwner()
                && (! $actor->isAdmin() || $actor->branch_id !== $source->getKey())
            )
        ) {
            throw new AuthorizationException('Anda tidak dapat membuat mutasi dari cabang tersebut.');
        }
    }

    private function authorizeOwner(User $actor): void
    {
        if (! $actor->is_active || ! $actor->isOwner()) {
            throw new AuthorizationException('Hanya Owner yang dapat memproses mutasi.');
        }
    }

    private function normalizeText(string $value, string $field, string $label): string
    {
        $normalized = trim($value);

        if (mb_strlen($normalized) < 10 || mb_strlen($normalized) > 1000) {
            throw ValidationException::withMessages([
                $field => $label.' wajib berisi 10 sampai 1000 karakter.',
            ]);
        }

        return $normalized;
    }
}
