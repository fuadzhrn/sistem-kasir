<?php

namespace App\Services\Demo;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleVoid;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoIntegrityService
{
    /**
     * @return array{passes: array<int, string>, warnings: array<int, string>, failures: array<int, string>, metrics: array<string, int|float|string>}
     */
    public function verify(): array
    {
        $result = [
            'passes' => [],
            'warnings' => [],
            'failures' => [],
            'metrics' => [],
        ];

        $requiredTables = [
            'branches', 'users', 'products', 'branch_stocks', 'stock_movements',
            'sales', 'sale_items', 'sale_voids', 'expenses', 'activity_logs',
        ];
        $missing = array_values(array_filter(
            $requiredTables,
            static fn (string $table): bool => ! Schema::hasTable($table),
        ));

        if ($missing !== []) {
            $result['failures'][] = 'Tabel wajib belum tersedia: '.implode(', ', $missing).'.';

            return $result;
        }

        $branches = Branch::query()->where('code', 'like', 'DMO%')->get();
        $users = User::query()->where('username', 'like', 'demo_%')->get();
        $products = Product::query()->where('code', 'like', 'DMO-%')->get();
        $cashierIds = $users->filter(fn (User $user): bool => $user->isCashier())->pluck('id');
        $productIds = $products->pluck('id');
        $branchIds = $branches->pluck('id');
        $sales = Sale::query()
            ->whereIn('cashier_id', $cashierIds)
            ->with(['items', 'paymentMethod', 'saleVoid'])
            ->get();
        $saleIds = $sales->pluck('id');
        $stocks = BranchStock::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->get();

        $result['metrics'] = [
            'branches' => $branches->count(),
            'users' => $users->count(),
            'products' => $products->count(),
            'branch_stocks' => $stocks->count(),
            'sales' => $sales->count(),
            'sale_items' => $sales->sum(fn (Sale $sale): int => $sale->items->count()),
            'voids' => $sales->where('status', Sale::STATUS_VOIDED)->count(),
            'expenses' => Expense::query()->whereIn('branch_id', $branchIds)->count(),
            'activities' => ActivityLog::query()->whereIn('branch_id', $branchIds)->count(),
        ];

        $this->check($result, $branches->count() === 4, 'Empat cabang demo tersedia.');
        $this->check($result, $users->where('username', 'demo_owner')->count() === 1, 'Owner demo tunggal tersedia.');
        $this->check($result, $products->isNotEmpty(), 'Katalog produk demo tersedia.');
        $this->check(
            $result,
            $stocks->where(fn (BranchStock $stock): bool => (float) $stock->quantity < 0)->isEmpty(),
            'Tidak ada stok demo negatif.',
        );

        $duplicateStocks = BranchStock::query()
            ->select(['branch_id', 'product_id'])
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->groupBy(['branch_id', 'product_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        $this->check($result, ! $duplicateStocks, 'Tidak ada pasangan stok cabang-produk ganda.');

        $movementInvalid = StockMovement::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->get()
            ->contains(function (StockMovement $movement): bool {
                $calculated = (float) $movement->quantity_before + (float) $movement->quantity_change;

                return abs($calculated - (float) $movement->quantity_after) > 0.0011;
            });
        $this->check($result, ! $movementInvalid, 'Aritmetika seluruh StockMovement demo konsisten.');

        $invalidSales = $sales->filter(function (Sale $sale): bool {
            if ($sale->items->isEmpty()) {
                return true;
            }

            $gross = $sale->items->sum(
                fn ($item): float => (float) $item->subtotal + (float) $item->discount_amount,
            );
            $discount = $sale->items->sum(fn ($item): float => (float) $item->discount_amount);
            $net = $sale->items->sum(fn ($item): float => (float) $item->subtotal);
            $cost = $sale->items->sum(
                fn ($item): float => round((float) $item->cost_price * (float) $item->quantity, 2),
            );

            return abs($gross - (float) $sale->subtotal) > 0.02
                || abs($discount - (float) $sale->discount_amount) > 0.02
                || abs($net - (float) $sale->total) > 0.02
                || abs($cost - (float) $sale->total_cost) > 0.05
                || abs(((float) $sale->total - (float) $sale->total_cost) - (float) $sale->gross_profit) > 0.02;
        });
        $this->check($result, $invalidSales->isEmpty(), 'Subtotal, diskon, total, HPP, dan laba Sale demo konsisten.');

        $invoiceDuplicates = Sale::query()
            ->whereIn('id', $saleIds)
            ->select('invoice_number')
            ->groupBy('invoice_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        $this->check($result, ! $invoiceDuplicates, 'Nomor nota demo unik.');

        $invalidVoids = SaleVoid::query()
            ->whereIn('sale_id', $saleIds)
            ->get()
            ->groupBy('sale_id')
            ->contains(fn ($group): bool => $group->count() !== 1);
        $voidStatusMismatch = $sales->contains(function (Sale $sale): bool {
            return ($sale->status === Sale::STATUS_VOIDED) !== ($sale->saleVoid !== null);
        });
        $this->check($result, ! $invalidVoids && ! $voidStatusMismatch, 'SaleVoid demo tunggal dan selaras dengan status Sale.');

        $voidMovementCount = StockMovement::query()
            ->where('movement_type', StockMovement::TYPE_VOID_SALE)
            ->whereIn('reference_id', SaleVoid::query()->whereIn('sale_id', $saleIds)->pluck('id'))
            ->count();
        $voidItemCount = $sales
            ->where('status', Sale::STATUS_VOIDED)
            ->sum(fn (Sale $sale): int => $sale->items->groupBy('product_id')->count());
        $this->check($result, $voidMovementCount === $voidItemCount, 'Restorasi stok pembatalan tercatat tepat satu kali per produk.');

        $expenses = Expense::query()->whereIn('branch_id', $branchIds)->get();
        $invalidExpenses = $expenses->contains(function (Expense $expense): bool {
            return match ($expense->status) {
                Expense::STATUS_APPROVED => $expense->approved_by === null || $expense->approved_at === null,
                Expense::STATUS_REJECTED => $expense->rejected_by === null
                    || $expense->rejected_at === null
                    || blank($expense->rejection_reason),
                Expense::STATUS_PENDING => $expense->approved_by !== null || $expense->rejected_by !== null,
                default => true,
            };
        });
        $this->check($result, ! $invalidExpenses, 'Workflow pengeluaran pending/approved/rejected konsisten.');

        $todayCoverage = Sale::query()
            ->whereIn('cashier_id', $cashierIds)
            ->whereDate('transaction_date', today())
            ->where('status', Sale::STATUS_COMPLETED)
            ->distinct()
            ->count('branch_id');
        $this->check($result, $todayCoverage === 4, 'Dashboard hari ini memiliki transaksi completed pada semua cabang.');

        if (DB::connection()->getDriverName() === 'sqlite') {
            $activeMonths = Sale::query()
                ->whereIn('cashier_id', $cashierIds)
                ->where('status', Sale::STATUS_COMPLETED)
                ->selectRaw("strftime('%Y-%m', transaction_date) as period")
                ->distinct()
                ->count();
        } else {
            $activeMonths = Sale::query()
                ->whereIn('cashier_id', $cashierIds)
                ->where('status', Sale::STATUS_COMPLETED)
                ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as period")
                ->distinct()
                ->count();
        }

        $this->check($result, $activeMonths >= 6, 'Data tren penjualan mencakup beberapa bulan.');
        $this->check(
            $result,
            $stocks->contains(fn (BranchStock $stock): bool => (float) $stock->quantity === 0.0),
            'Stok berstatus Habis tersedia.',
        );
        $this->check(
            $result,
            $stocks->contains(function (BranchStock $stock) use ($products): bool {
                $product = $products->firstWhere('id', $stock->product_id);

                return $product !== null
                    && (float) $stock->quantity > 0
                    && (float) $stock->quantity <= (float) $product->minimum_stock;
            }),
            'Stok berstatus Menipis tersedia.',
        );
        $this->check(
            $result,
            $stocks->contains(function (BranchStock $stock) use ($products): bool {
                $product = $products->firstWhere('id', $stock->product_id);

                return $product !== null && (float) $stock->quantity > (float) $product->minimum_stock;
            }),
            'Stok berstatus Aman tersedia.',
        );
        $this->check(
            $result,
            $stocks->count() < $branches->count() * $products->count(),
            'Produk yang belum tersedia pada cabang tertentu tetap ada.',
        );

        $this->check(
            $result,
            StockReceipt::query()->whereIn('branch_id', $branchIds)->exists()
                && PriceHistory::query()->whereIn('product_id', $productIds)->exists()
                && $expenses->isNotEmpty(),
            'Prasyarat laporan barang masuk, harga, penjualan, stok, dan pengeluaran tersedia.',
        );

        $activityQuery = ActivityLog::query()->where(function (Builder $query) use ($branchIds): void {
            $query->whereIn('branch_id', $branchIds)
                ->orWhereNull('branch_id');
        });
        $requiredActions = [
            'product_created', 'initial_stock_created', 'stock_receipt_created',
            'sale_created', 'sale_voided', 'receipt_reprint_requested',
            'expense_created', 'store_settings_updated', 'login_failed',
        ];
        $existingActions = (clone $activityQuery)->whereIn('action', $requiredActions)->pluck('action')->unique();
        $missingActions = array_values(array_diff($requiredActions, $existingActions->all()));
        $this->check(
            $result,
            $missingActions === [],
            'Aktivitas utama demo tersedia.',
            $missingActions === [] ? null : 'Aktivitas belum tersedia: '.implode(', ', $missingActions).'.',
        );

        $sensitiveLog = (clone $activityQuery)
            ->get(['description', 'metadata'])
            ->contains(function (ActivityLog $log): bool {
                $content = mb_strtolower($log->description.' '.json_encode($log->metadata));

                return str_contains($content, 'password')
                    || str_contains($content, 'app_key')
                    || str_contains($content, 'db_password');
            });
        $this->check($result, ! $sensitiveLog, 'ActivityLog demo tidak memuat credential sensitif.');

        return $result;
    }

    /**
     * @param  array{passes: array<int, string>, warnings: array<int, string>, failures: array<int, string>, metrics: array<string, int|float|string>}  $result
     */
    private function check(
        array &$result,
        bool $condition,
        string $success,
        ?string $failure = null,
        bool $warning = false,
    ): void {
        if ($condition) {
            $result['passes'][] = $success;

            return;
        }

        $result[$warning ? 'warnings' : 'failures'][] = $failure ?? $success;
    }
}
