<?php

namespace App\Services\StockReceipt;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;
use App\Services\Calculation\WeightedAverageCostCalculator;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockReceiptService
{
    public function __construct(
        private readonly StockReceiptNumberService $numberService,
        private readonly WeightedAverageCostCalculator $calculator,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function create(
        Branch $branch,
        CarbonInterface $receiptDate,
        ?string $supplierName,
        ?string $notes,
        array $items,
        User $actor,
    ): StockReceipt {
        if (
            (! $actor->isOwner() && ! $actor->isAdmin())
            || ! $this->branchAccess->canAccessBranch($actor, $branch)
        ) {
            throw new AuthorizationException('Anda tidak memiliki akses ke cabang tersebut.');
        }

        $normalizedItems = $this->normalizeItems($items);
        $this->validateNoDuplicateProducts($normalizedItems);

        return DB::transaction(function () use (
            $branch,
            $receiptDate,
            $supplierName,
            $notes,
            $normalizedItems,
            $actor,
        ): StockReceipt {
            $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());

            if (! $lockedBranch->is_active) {
                throw ValidationException::withMessages(['branch_id' => 'Cabang tidak aktif.']);
            }

            $productIds = array_column($normalizedItems, 'product_id');
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak tersedia atau tidak aktif.',
                ]);
            }

            $receipt = StockReceipt::query()->create([
                'branch_id' => $lockedBranch->getKey(),
                'receipt_number' => $this->numberService->generate($lockedBranch, $receiptDate),
                'receipt_date' => $receiptDate->toDateString(),
                'supplier_name' => $this->nullableTrimmed($supplierName),
                'total_cost' => '0.00',
                'notes' => $this->nullableTrimmed($notes),
                'created_by' => $actor->getKey(),
            ]);
            $totalCost = '0.00';

            foreach ($normalizedItems as $item) {
                $product = $products->get($item['product_id']);
                $branchStock = $this->getOrCreateLockedBranchStock($lockedBranch, $product);
                $quantityBefore = (string) $branchStock->quantity;
                $averageCostBefore = (string) $branchStock->average_cost;
                $quantityAfter = $this->calculator->addQuantity($quantityBefore, $item['quantity']);
                $averageCostAfter = $this->calculator->calculateWeightedAverage(
                    $quantityBefore,
                    $averageCostBefore,
                    $item['quantity'],
                    $item['purchase_price'],
                );
                $subtotal = $this->calculator->calculateSubtotal(
                    $item['quantity'],
                    $item['purchase_price'],
                );

                StockReceiptItem::query()->create([
                    'stock_receipt_id' => $receipt->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $subtotal,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'average_cost_before' => $averageCostBefore,
                    'average_cost_after' => $averageCostAfter,
                ]);

                $branchStock->update([
                    'quantity' => $quantityAfter,
                    'average_cost' => $averageCostAfter,
                ]);

                StockMovement::query()->create([
                    'branch_id' => $lockedBranch->getKey(),
                    'product_id' => $product->getKey(),
                    'created_by' => $actor->getKey(),
                    'movement_type' => StockMovement::TYPE_PURCHASE,
                    'reference_type' => StockReceipt::class,
                    'reference_id' => $receipt->getKey(),
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $item['quantity'],
                    'quantity_after' => $quantityAfter,
                    'unit_cost' => $item['purchase_price'],
                    'notes' => $this->buildMovementNotes($receipt),
                ]);

                $totalCost = $this->calculator->addMoney($totalCost, $subtotal);
            }

            $receipt->update(['total_cost' => $totalCost]);

            return $receipt->refresh();
        }, 3);
    }

    public function normalizeItems(array $items): array
    {
        $normalized = array_map(fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'quantity' => $this->calculator->normalizeQuantity((string) $item['quantity']),
            'purchase_price' => $this->calculator->normalizeMoney((string) $item['purchase_price']),
        ], $items);

        usort($normalized, fn (array $left, array $right): int => $left['product_id'] <=> $right['product_id']);

        return $normalized;
    }

    public function validateNoDuplicateProducts(array $items): void
    {
        $productIds = array_column($items, 'product_id');

        if (count($productIds) !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'items' => 'Produk duplikat ditemukan. Satu produk hanya boleh dipilih satu kali.',
            ]);
        }
    }

    public function getOrCreateLockedBranchStock(Branch $branch, Product $product): BranchStock
    {
        BranchStock::query()->insertOrIgnore([
            'branch_id' => $branch->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => '0.000',
            'average_cost' => '0.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return BranchStock::query()
            ->where('branch_id', $branch->getKey())
            ->where('product_id', $product->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function buildMovementNotes(StockReceipt $receipt): string
    {
        $supplier = $receipt->supplier_name === null
            ? 'supplier tidak dicantumkan'
            : 'supplier '.$receipt->supplier_name;

        return 'Barang masuk '.$receipt->receipt_number.' - '.$supplier;
    }

    private function nullableTrimmed(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
