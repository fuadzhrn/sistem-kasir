<?php

namespace App\Services\Sale;

use App\Exceptions\Sale\CartPriceChangedException;
use App\Exceptions\Sale\DiscountLimitExceededException;
use App\Exceptions\Sale\DuplicateCheckoutTokenException;
use App\Exceptions\Sale\InsufficientPaymentException;
use App\Exceptions\Sale\InsufficientStockException;
use App\Exceptions\Sale\SaleCheckoutException;
use App\Exceptions\Sale\StockCostNotReadyException;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleService
{
    public function __construct(
        private readonly SaleCalculator $calculator,
        private readonly SaleDiscountAllocator $discountAllocator,
        private readonly SaleNumberService $numberService,
        private readonly BranchAccessService $branchAccess,
    ) {}

    /**
     * @param  array<int, array{product_id: int|string, quantity: string|int|float}>  $items
     */
    public function createSale(
        User $actor,
        Branch $branch,
        array $items,
        string $discountAmount,
        PaymentMethod $paymentMethod,
        ?string $amountReceived,
        string $checkoutToken,
        string $paymentAction,
        ?string $notes,
        ?string $expectedSubtotal,
        ?string $expectedTotal,
        ?string $ipAddress,
        ?string $userAgent,
    ): Sale {
        $this->validateActorAndBranch($actor, $branch);
        $normalizedItems = $this->normalizeItems($items);
        $this->validateNoDuplicateProducts($normalizedItems);
        $discountAmount = $this->calculator->normalizeMoney($discountAmount);
        $amountReceived = $amountReceived === null
            ? null
            : $this->calculator->normalizeMoney($amountReceived);
        $expectedSubtotal = $expectedSubtotal === null
            ? null
            : $this->calculator->normalizeMoney($expectedSubtotal);
        $expectedTotal = $expectedTotal === null
            ? null
            : $this->calculator->normalizeMoney($expectedTotal);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(function () use (
                    $actor,
                    $branch,
                    $normalizedItems,
                    $discountAmount,
                    $paymentMethod,
                    $amountReceived,
                    $checkoutToken,
                    $paymentAction,
                    $notes,
                    $expectedSubtotal,
                    $expectedTotal,
                    $ipAddress,
                    $userAgent,
                ): Sale {
                    $lockedBranch = Branch::query()
                        ->lockForUpdate()
                        ->find($branch->getKey());

                    if ($lockedBranch === null || ! $lockedBranch->is_active) {
                        throw new SaleCheckoutException(
                            'BRANCH_INACTIVE',
                            'Cabang tidak tersedia atau tidak aktif.',
                            409,
                        );
                    }

                    $existingSale = Sale::query()
                        ->where('checkout_token', $checkoutToken)
                        ->lockForUpdate()
                        ->first();

                    if ($existingSale !== null) {
                        return $this->resolveIdempotentSale($existingSale, $actor, $lockedBranch);
                    }

                    $lockedPaymentMethod = PaymentMethod::query()
                        ->whereKey($paymentMethod->getKey())
                        ->lockForUpdate()
                        ->first();

                    if ($lockedPaymentMethod === null || ! $lockedPaymentMethod->is_active) {
                        throw new SaleCheckoutException(
                            'PAYMENT_METHOD_INACTIVE',
                            'Metode pembayaran tidak tersedia atau tidak aktif.',
                            422,
                        );
                    }

                    $products = $this->loadProducts($normalizedItems);
                    $stocks = $this->lockBranchStocks($lockedBranch, $normalizedItems, $products);
                    $lines = $this->calculateLines($normalizedItems, $products, $stocks);
                    $subtotal = $this->calculator->sumMoney(
                        array_column($lines, 'gross_subtotal'),
                    );
                    $this->validateDiscount($actor, $discountAmount, $subtotal);
                    $total = $this->calculateTotal($subtotal, $discountAmount);
                    $this->validateExpectedTotals(
                        $expectedSubtotal,
                        $expectedTotal,
                        $subtotal,
                        $total,
                        $lines,
                    );
                    $allocations = $this->discountAllocator->allocate(
                        array_column($lines, 'gross_subtotal'),
                        $discountAmount,
                    );
                    $lines = $this->applyDiscountAndProfit($lines, $allocations);
                    $totalCost = $this->calculator->sumMoney(array_column($lines, 'line_cost'));
                    $grossProfit = $this->calculator->calculateProfit($total, $totalCost);
                    [$amountPaid, $changeAmount] = $this->calculatePayment(
                        $lockedPaymentMethod,
                        $amountReceived,
                        $total,
                    );
                    $transactionDate = now();
                    $sale = Sale::query()->create([
                        'branch_id' => $lockedBranch->getKey(),
                        'cashier_id' => $actor->getKey(),
                        'payment_method_id' => $lockedPaymentMethod->getKey(),
                        'invoice_number' => $this->numberService->generate(
                            $lockedBranch,
                            $transactionDate,
                        ),
                        'checkout_token' => $checkoutToken,
                        'transaction_date' => $transactionDate,
                        'subtotal' => $subtotal,
                        'discount_amount' => $discountAmount,
                        'total' => $total,
                        'amount_paid' => $amountPaid,
                        'change_amount' => $changeAmount,
                        'total_cost' => $totalCost,
                        'gross_profit' => $grossProfit,
                        'payment_method_name' => $lockedPaymentMethod->name,
                        'status' => Sale::STATUS_COMPLETED,
                        'notes' => $this->nullableTrimmed($notes),
                        'voided_at' => null,
                    ]);

                    $this->persistLinesAndStocks(
                        $sale,
                        $actor,
                        $lockedBranch,
                        $stocks,
                        $lines,
                    );
                    $this->createActivityLog(
                        $sale,
                        $actor,
                        $lockedBranch,
                        $ipAddress,
                        $userAgent,
                    );

                    return $this->prepareResult($sale, false, $paymentAction);
                }, 3);
            } catch (QueryException $exception) {
                $existingSale = Sale::query()
                    ->where('checkout_token', $checkoutToken)
                    ->first();

                if ($existingSale !== null && $this->isUniqueConstraintViolation($exception)) {
                    return $this->resolveIdempotentSale($existingSale, $actor, $branch);
                }

                if ($this->isUniqueConstraintViolation($exception) && $attempt < 3) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new SaleCheckoutException(
            'CHECKOUT_FAILED',
            'Transaksi belum dapat diproses. Silakan coba kembali.',
            500,
        );
    }

    /**
     * @param  array<int, array{product_id: int|string, quantity: string|int|float}>  $items
     * @return array<int, array{product_id: int, quantity: string}>
     */
    public function normalizeItems(array $items): array
    {
        $normalized = array_map(fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'quantity' => $this->calculator->normalizeQuantity((string) $item['quantity']),
        ], $items);

        usort(
            $normalized,
            fn (array $left, array $right): int => $left['product_id'] <=> $right['product_id'],
        );

        return $normalized;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: string}>  $items
     */
    public function validateNoDuplicateProducts(array $items): void
    {
        $productIds = array_column($items, 'product_id');

        if (count($productIds) !== count(array_unique($productIds))) {
            throw new SaleCheckoutException(
                'PRODUCT_NOT_AVAILABLE',
                'Produk duplikat tidak diperbolehkan dalam satu transaksi.',
                422,
            );
        }
    }

    private function validateActorAndBranch(User $actor, Branch $branch): void
    {
        if (
            ! $actor->is_active
            || ! $actor->hasAnyRole(['owner', 'admin', 'cashier'])
            || ! $this->branchAccess->canAccessBranch($actor, $branch)
        ) {
            throw new SaleCheckoutException(
                'BRANCH_NOT_ALLOWED',
                'Anda tidak memiliki akses transaksi pada cabang tersebut.',
                403,
            );
        }
    }

    /**
     * @param  array<int, array{product_id: int, quantity: string}>  $items
     * @return Collection<int, Product>
     */
    private function loadProducts(array $items): Collection
    {
        $productIds = array_column($items, 'product_id');
        $products = Product::query()
            ->with([
                'category:id,name,is_active',
                'unit:id,name,symbol,is_active',
            ])
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw new SaleCheckoutException(
                'PRODUCT_NOT_AVAILABLE',
                'Salah satu produk tidak tersedia.',
                422,
            );
        }

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if (
                $product === null
                || ! $product->is_active
                || $product->category === null
                || ! $product->category->is_active
                || $product->unit === null
                || ! $product->unit->is_active
            ) {
                throw new SaleCheckoutException(
                    'PRODUCT_INACTIVE',
                    'Salah satu produk, kategori, atau satuannya tidak aktif.',
                    422,
                );
            }

            try {
                $sellingPrice = $this->calculator->normalizeMoney(
                    (string) $product->selling_price,
                );
            } catch (InvalidArgumentException) {
                throw new SaleCheckoutException(
                    'PRODUCT_NOT_AVAILABLE',
                    'Harga jual salah satu produk belum valid.',
                    422,
                );
            }

            if ($this->calculator->compareMoney($sellingPrice, '0.00') < 0) {
                throw new SaleCheckoutException(
                    'PRODUCT_NOT_AVAILABLE',
                    'Harga jual salah satu produk belum valid.',
                    422,
                );
            }
        }

        return $products;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: string}>  $items
     * @param  Collection<int, Product>  $products
     * @return Collection<int, BranchStock>
     */
    private function lockBranchStocks(
        Branch $branch,
        array $items,
        Collection $products,
    ): Collection {
        $productIds = array_column($items, 'product_id');
        $stocks = BranchStock::query()
            ->where('branch_id', $branch->getKey())
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        if ($stocks->count() !== count($productIds)) {
            foreach ($items as $item) {
                if (! $stocks->has($item['product_id'])) {
                    $product = $products->get($item['product_id']);

                    throw new InsufficientStockException([
                        'product_id' => $item['product_id'],
                        'product_name' => $product?->name ?? 'Produk',
                        'requested_quantity' => $item['quantity'],
                        'available_quantity' => '0.000',
                    ]);
                }
            }
        }

        return $stocks;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: string}>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, BranchStock>  $stocks
     * @return array<int, array<string, mixed>>
     */
    private function calculateLines(
        array $items,
        Collection $products,
        Collection $stocks,
    ): array {
        $lines = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $stock = $stocks->get($item['product_id']);
            $quantityBefore = $this->calculator->normalizeQuantity((string) $stock->quantity);

            if ($this->calculator->compareQuantity($item['quantity'], $quantityBefore) > 0) {
                throw new InsufficientStockException([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $quantityBefore,
                ]);
            }

            $costPrice = $this->calculator->normalizeMoney((string) $stock->average_cost);

            if (
                $this->calculator->compareQuantity($quantityBefore, '0.000') > 0
                && $this->calculator->compareMoney($costPrice, '0.00') <= 0
            ) {
                throw new StockCostNotReadyException;
            }

            $sellingPrice = $this->calculator->normalizeMoney(
                (string) $product->selling_price,
            );
            $grossSubtotal = $this->calculator->calculateLineSubtotal(
                $item['quantity'],
                $sellingPrice,
            );
            $lineCost = $this->calculator->calculateLineCost(
                $item['quantity'],
                $costPrice,
            );

            $lines[] = [
                'product' => $product,
                'stock' => $stock,
                'quantity' => $item['quantity'],
                'quantity_before' => $quantityBefore,
                'quantity_after' => $this->calculator->subtractQuantity(
                    $quantityBefore,
                    $item['quantity'],
                ),
                'selling_price' => $sellingPrice,
                'cost_price' => $costPrice,
                'gross_subtotal' => $grossSubtotal,
                'line_cost' => $lineCost,
            ];
        }

        return $lines;
    }

    private function validateDiscount(User $actor, string $discount, string $subtotal): void
    {
        if (
            $this->calculator->compareMoney($discount, '0.00') < 0
            || $this->calculator->compareMoney($discount, $subtotal) > 0
        ) {
            throw new SaleCheckoutException(
                'INVALID_DISCOUNT',
                'Diskon tidak valid atau melebihi subtotal.',
                422,
            );
        }

        if (! $actor->isCashier()) {
            return;
        }

        $setting = Setting::query()
            ->where('key', 'maximum_cashier_discount')
            ->lockForUpdate()
            ->value('value');

        try {
            $limit = $setting === null
                ? '0.00'
                : $this->calculator->normalizeMoney((string) $setting);
        } catch (InvalidArgumentException) {
            $limit = '0.00';
        }

        if (
            $this->calculator->compareMoney($limit, '0.00') < 0
            || $this->calculator->compareMoney($discount, $limit) > 0
        ) {
            throw new DiscountLimitExceededException;
        }
    }

    private function calculateTotal(string $subtotal, string $discount): string
    {
        try {
            return $this->calculator->calculateTotal($subtotal, $discount);
        } catch (InvalidArgumentException) {
            throw new SaleCheckoutException(
                'INVALID_DISCOUNT',
                'Diskon tidak valid atau melebihi subtotal.',
                422,
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateExpectedTotals(
        ?string $expectedSubtotal,
        ?string $expectedTotal,
        string $subtotal,
        string $total,
        array $lines,
    ): void {
        if (
            ($expectedSubtotal !== null
                && $this->calculator->compareMoney($expectedSubtotal, $subtotal) !== 0)
            || ($expectedTotal !== null
                && $this->calculator->compareMoney($expectedTotal, $total) !== 0)
        ) {
            throw new CartPriceChangedException([
                'subtotal' => $subtotal,
                'total' => $total,
                'items' => array_map(
                    static fn (array $line): array => [
                        'product_id' => $line['product']->getKey(),
                        'product_name' => $line['product']->name,
                        'quantity' => $line['quantity'],
                        'selling_price' => $line['selling_price'],
                        'subtotal' => $line['gross_subtotal'],
                    ],
                    $lines,
                ),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, string>  $allocations
     * @return array<int, array<string, mixed>>
     */
    private function applyDiscountAndProfit(array $lines, array $allocations): array
    {
        foreach ($lines as $index => $line) {
            $discount = $allocations[$index];
            $netSubtotal = $this->calculator->subtractMoney(
                $line['gross_subtotal'],
                $discount,
            );
            $lines[$index]['discount_amount'] = $discount;
            $lines[$index]['net_subtotal'] = $netSubtotal;
            $lines[$index]['profit'] = $this->calculator->calculateProfit(
                $netSubtotal,
                $line['line_cost'],
            );
        }

        return $lines;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function calculatePayment(
        PaymentMethod $paymentMethod,
        ?string $amountReceived,
        string $total,
    ): array {
        if ($paymentMethod->type !== 'cash') {
            return [$total, '0.00'];
        }

        if (
            $amountReceived === null
            || $this->calculator->compareMoney($amountReceived, $total) < 0
        ) {
            throw new InsufficientPaymentException;
        }

        return [
            $amountReceived,
            $this->calculator->calculateChange($amountReceived, $total),
        ];
    }

    /**
     * @param  Collection<int, BranchStock>  $stocks
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function persistLinesAndStocks(
        Sale $sale,
        User $actor,
        Branch $branch,
        Collection $stocks,
        array $lines,
    ): void {
        foreach ($lines as $line) {
            $product = $line['product'];
            $stock = $stocks->get($product->getKey());
            SaleItem::query()->create([
                'sale_id' => $sale->getKey(),
                'product_id' => $product->getKey(),
                'product_code' => $product->code,
                'product_name' => $product->name,
                'unit_name' => $product->unit->name,
                'product_size' => $product->size,
                'quantity' => $line['quantity'],
                'selling_price' => $line['selling_price'],
                'cost_price' => $line['cost_price'],
                'discount_amount' => $line['discount_amount'],
                'subtotal' => $line['net_subtotal'],
                'profit' => $line['profit'],
            ]);
            $stock->update(['quantity' => $line['quantity_after']]);
            StockMovement::query()->create([
                'branch_id' => $branch->getKey(),
                'product_id' => $product->getKey(),
                'created_by' => $actor->getKey(),
                'movement_type' => StockMovement::TYPE_SALE,
                'reference_type' => Sale::class,
                'reference_id' => $sale->getKey(),
                'quantity_before' => $line['quantity_before'],
                'quantity_change' => $this->calculator->negateQuantity($line['quantity']),
                'quantity_after' => $line['quantity_after'],
                'unit_cost' => $line['cost_price'],
                'notes' => 'Penjualan '.$sale->invoice_number,
            ]);
        }
    }

    private function createActivityLog(
        Sale $sale,
        User $actor,
        Branch $branch,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        ActivityLog::query()->create([
            'user_id' => $actor->getKey(),
            'branch_id' => $branch->getKey(),
            'action' => 'sale_created',
            'module' => 'sales',
            'reference_type' => Sale::class,
            'reference_id' => $sale->getKey(),
            'description' => 'Transaksi '.$sale->invoice_number
                .' berhasil dibuat dengan total '.$this->formatRupiah((string) $sale->total).'.',
            'ip_address' => $this->limitedText($ipAddress, 45),
            'user_agent' => $this->limitedText($userAgent, 1000),
        ]);
    }

    private function resolveIdempotentSale(
        Sale $sale,
        User $actor,
        Branch $branch,
    ): Sale {
        if (
            (int) $sale->cashier_id !== (int) $actor->getKey()
            || (int) $sale->branch_id !== (int) $branch->getKey()
        ) {
            throw new DuplicateCheckoutTokenException;
        }

        return $this->prepareResult($sale, true, null);
    }

    private function prepareResult(
        Sale $sale,
        bool $idempotent,
        ?string $paymentAction,
    ): Sale {
        $sale->loadMissing([
            'branch:id,name',
            'paymentMethod:id,code,name,type',
        ])->loadCount('items');
        $sale->setAttribute('checkout_idempotent', $idempotent);
        $sale->setAttribute('requested_payment_action', $paymentAction);

        return $sale;
    }

    private function nullableTrimmed(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function limitedText(?string $value, int $limit): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : mb_substr($trimmed, 0, $limit);
    }

    private function formatRupiah(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '00');
        $formatted = 'Rp'.number_format((int) $whole, 0, ',', '.');

        return rtrim($fraction, '0') === ''
            ? $formatted
            : $formatted.','.str_pad($fraction, 2, '0');
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array($sqlState, ['23000', '23505'], true)
            || str_contains(mb_strtolower($exception->getMessage()), 'unique constraint');
    }
}
