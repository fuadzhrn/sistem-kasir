<?php

namespace App\Services\Sale;

use App\Exceptions\Sale\RefundConfirmationRequiredException;
use App\Exceptions\Sale\SaleCannotBeVoidedException;
use App\Exceptions\Sale\SaleVoidStockException;
use App\Models\ActivityLog;
use App\Models\BranchStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleVoid;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Calculation\WeightedAverageCostCalculator;
use App\Support\Format\Rupiah;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleVoidService
{
    public function __construct(
        private readonly SaleCalculator $saleCalculator,
        private readonly WeightedAverageCostCalculator $costCalculator,
    ) {}

    public function voidSale(
        Sale $sale,
        User $actor,
        string $reason,
        bool $refundConfirmed,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): SaleVoid {
        return DB::transaction(function () use (
            $sale,
            $actor,
            $reason,
            $refundConfirmed,
            $ipAddress,
            $userAgent,
        ): SaleVoid {
            $lockedSale = Sale::query()
                ->with(['paymentMethod:id,type', 'saleVoid'])
                ->lockForUpdate()
                ->findOrFail($sale->getKey());
            $existingVoid = SaleVoid::query()
                ->where('sale_id', $lockedSale->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedSale->isVoided()) {
                if ($existingVoid === null) {
                    throw new SaleCannotBeVoidedException(
                        'Data pembatalan transaksi tidak lengkap. Hubungi pengelola sistem.',
                    );
                }

                if (! $actor->can('view', $lockedSale)) {
                    throw new AuthorizationException;
                }

                return $this->prepareResult($existingVoid, true);
            }

            $legacyVoid = $lockedSale->status === Sale::STATUS_VOID_REQUESTED
                ? $existingVoid
                : null;

            if ($existingVoid !== null && $legacyVoid === null) {
                throw new SaleCannotBeVoidedException(
                    'Transaksi sudah mempunyai catatan pembatalan.',
                );
            }

            if (! $actor->can('void', $lockedSale)) {
                throw new AuthorizationException;
            }

            if (! $lockedSale->canBeVoided()) {
                throw new SaleCannotBeVoidedException;
            }

            $nonCash = $lockedSale->paymentMethod?->type !== 'cash';

            if ($nonCash && ! $refundConfirmed) {
                throw new RefundConfirmationRequiredException;
            }

            $items = SaleItem::query()
                ->where('sale_id', $lockedSale->getKey())
                ->orderBy('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $lines = $this->aggregateItems($items);
            $stocks = $this->lockStocks($lockedSale, $lines);
            $voidedAt = now();
            $voidAttributes = [
                'sale_id' => $lockedSale->getKey(),
                'branch_id' => $lockedSale->branch_id,
                'voided_by' => $actor->getKey(),
                'voided_at' => $voidedAt,
                'reason' => $reason,
                'original_subtotal' => $lockedSale->subtotal,
                'original_discount_amount' => $lockedSale->discount_amount,
                'original_total' => $lockedSale->total,
                'original_total_cost' => $lockedSale->total_cost,
                'original_gross_profit' => $lockedSale->gross_profit,
                'payment_method_name' => $lockedSale->payment_method_name,
                'refund_confirmed' => $nonCash && $refundConfirmed,
                'notes' => $nonCash
                    ? 'Pengembalian dana ditangani secara manual di luar aplikasi.'
                    : null,
                'status' => Sale::STATUS_VOIDED,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ];

            if ($legacyVoid === null) {
                $saleVoid = SaleVoid::query()->create([
                    ...$voidAttributes,
                    'requested_by' => $actor->getKey(),
                ]);
            } else {
                DB::table('sale_voids')
                    ->where('id', $legacyVoid->getKey())
                    ->update([
                        ...$voidAttributes,
                        'updated_at' => $voidedAt,
                    ]);
                $saleVoid = $legacyVoid->refresh();
            }

            $this->restoreStocks($lockedSale, $saleVoid, $actor, $reason, $lines, $stocks);
            $lockedSale->update([
                'status' => Sale::STATUS_VOIDED,
                'voided_at' => $voidedAt,
            ]);
            $this->createActivityLog(
                $lockedSale,
                $actor,
                $ipAddress,
                $userAgent,
            );

            return $this->prepareResult($saleVoid, false);
        }, 3);
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     * @return array<int, array{product_id: int, quantity: string, returned_value: string, unit_cost: string}>
     */
    private function aggregateItems(Collection $items): array
    {
        if ($items->isEmpty()) {
            throw new SaleVoidStockException;
        }

        $lines = [];

        foreach ($items as $item) {
            if ($item->product_id === null) {
                throw new SaleVoidStockException;
            }

            try {
                $quantity = $this->costCalculator->normalizeQuantity((string) $item->quantity);
                $cost = $this->costCalculator->normalizeMoney((string) $item->cost_price);
                $value = $this->costCalculator->multiplyQuantityByPrice($quantity, $cost);
            } catch (InvalidArgumentException) {
                throw new SaleVoidStockException;
            }

            if ($this->saleCalculator->compareQuantity($quantity, '0.000') <= 0
                || $this->saleCalculator->compareMoney($cost, '0.00') <= 0) {
                throw new SaleVoidStockException;
            }

            $productId = (int) $item->product_id;

            if (! isset($lines[$productId])) {
                $lines[$productId] = [
                    'product_id' => $productId,
                    'quantity' => '0.000',
                    'returned_value' => '0.00',
                    'unit_cost' => '0.00',
                ];
            }

            $lines[$productId]['quantity'] = $this->costCalculator->addQuantity(
                $lines[$productId]['quantity'],
                $quantity,
            );
            $lines[$productId]['returned_value'] = $this->costCalculator->addMoney(
                $lines[$productId]['returned_value'],
                $value,
            );
        }

        ksort($lines);

        foreach ($lines as &$line) {
            $line['unit_cost'] = $this->costCalculator->calculateUnitCostFromValue(
                $line['returned_value'],
                $line['quantity'],
            );
        }

        unset($line);

        return array_values($lines);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: string, returned_value: string, unit_cost: string}>  $lines
     * @return Collection<int, BranchStock>
     */
    private function lockStocks(Sale $sale, array $lines): Collection
    {
        $productIds = array_column($lines, 'product_id');
        $stocks = BranchStock::query()
            ->where('branch_id', $sale->branch_id)
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        if ($stocks->count() !== count($productIds)) {
            throw new SaleVoidStockException;
        }

        return $stocks;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: string, returned_value: string, unit_cost: string}>  $lines
     * @param  Collection<int, BranchStock>  $stocks
     */
    private function restoreStocks(
        Sale $sale,
        SaleVoid $saleVoid,
        User $actor,
        string $reason,
        array $lines,
        Collection $stocks,
    ): void {
        foreach ($lines as $line) {
            $stock = $stocks->get($line['product_id']);

            if (! $stock instanceof BranchStock) {
                throw new SaleVoidStockException;
            }

            try {
                $quantityBefore = $this->costCalculator->normalizeQuantity((string) $stock->quantity);
                $quantityAfter = $this->costCalculator->addQuantity($quantityBefore, $line['quantity']);
                $averageCostAfter = $this->costCalculator->calculateWeightedAverageFromValue(
                    $quantityBefore,
                    (string) $stock->average_cost,
                    $line['quantity'],
                    $line['returned_value'],
                );
            } catch (InvalidArgumentException) {
                throw new SaleVoidStockException;
            }

            $stock->update([
                'quantity' => $quantityAfter,
                'average_cost' => $averageCostAfter,
            ]);
            StockMovement::query()->create([
                'branch_id' => $sale->branch_id,
                'product_id' => $line['product_id'],
                'created_by' => $actor->getKey(),
                'movement_type' => StockMovement::TYPE_VOID_SALE,
                'reference_type' => SaleVoid::class,
                'reference_id' => $saleVoid->getKey(),
                'quantity_before' => $quantityBefore,
                'quantity_change' => $line['quantity'],
                'quantity_after' => $quantityAfter,
                'unit_cost' => $line['unit_cost'],
                'notes' => 'Pembatalan nota '.$sale->invoice_number
                    .'. Alasan: '.mb_substr($reason, 0, 500),
            ]);
        }
    }

    private function createActivityLog(
        Sale $sale,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        ActivityLog::query()->create([
            'user_id' => $actor->getKey(),
            'branch_id' => $sale->branch_id,
            'action' => 'sale_voided',
            'module' => 'sales',
            'reference_type' => Sale::class,
            'reference_id' => $sale->getKey(),
            'description' => 'Transaksi '.$sale->invoice_number
                .' sebesar '.Rupiah::format((string) $sale->total).' dibatalkan.',
            'ip_address' => $this->limitedText($ipAddress, 45),
            'user_agent' => $this->limitedText($userAgent, 1000),
        ]);
    }

    private function prepareResult(SaleVoid $saleVoid, bool $idempotent): SaleVoid
    {
        $saleVoid->loadMissing(['sale:id,invoice_number,status,voided_at', 'voider:id,name']);
        $saleVoid->setAttribute('void_idempotent', $idempotent);

        return $saleVoid;
    }

    private function limitedText(?string $value, int $limit): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
