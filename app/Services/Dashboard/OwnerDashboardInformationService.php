<?php

namespace App\Services\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Format\Quantity;
use App\Support\Format\Rupiah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

final class OwnerDashboardInformationService
{
    /**
     * @return array<string, mixed>
     */
    public function build(
        ?Branch $branch,
        OwnerDashboardDateRange $dateRange,
    ): array {
        return [
            'top_products' => $this->topProducts($branch, $dateRange),
            'low_stocks' => $this->lowStocks($branch),
            'latest_transactions' => $this->latestTransactions($branch, $dateRange),
            'latest_expenses' => $this->latestExpenses($branch, $dateRange),
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function topProducts(
        ?Branch $branch,
        OwnerDashboardDateRange $range,
    ): array {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.transaction_date', [$range->start, $range->end])
            ->when($branch, fn (Builder $query): Builder => $query->where(
                'sales.branch_id',
                $branch->getKey(),
            ))
            ->selectRaw('sale_items.product_id')
            ->selectRaw('MAX(products.code) AS current_code')
            ->selectRaw('MAX(products.name) AS current_name')
            ->selectRaw('MAX(units.name) AS current_unit')
            ->selectRaw('MAX(sale_items.product_code) AS snapshot_code')
            ->selectRaw('MAX(sale_items.product_name) AS snapshot_name')
            ->selectRaw('MAX(sale_items.unit_name) AS snapshot_unit')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) AS quantity_sold')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) AS net_sales')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) AS receipt_count')
            ->groupBy('sale_items.product_id')
            ->groupByRaw(
                "CASE WHEN sale_items.product_id IS NULL THEN sale_items.product_code ELSE '' END",
            )
            ->orderByDesc('net_sales')
            ->limit(10)
            ->get()
            ->values()
            ->map(fn ($row, int $index): array => [
                'rank' => $index + 1,
                'code' => (string) ($row->current_code ?: $row->snapshot_code),
                'name' => (string) ($row->current_name ?: $row->snapshot_name),
                'unit' => (string) ($row->current_unit ?: $row->snapshot_unit),
                'quantity' => $this->quantity((string) $row->quantity_sold),
                'quantity_value' => (string) $row->quantity_sold,
                'net_sales' => (string) $row->net_sales,
                'net_sales_formatted' => Rupiah::format((string) $row->net_sales),
                'receipt_count' => (int) $row->receipt_count,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function lowStocks(?Branch $branch): array
    {
        return BranchStock::query()
            ->join('products', 'products.id', '=', 'branch_stocks.product_id')
            ->join('branches', 'branches.id', '=', 'branch_stocks.branch_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->when($branch, fn (Builder $query): Builder => $query->where(
                'branch_stocks.branch_id',
                $branch->getKey(),
            ))
            ->whereColumn('branch_stocks.quantity', '<=', 'products.minimum_stock')
            ->orderByRaw('CASE WHEN branch_stocks.quantity <= 0 THEN 0 ELSE 1 END')
            ->orderBy('branch_stocks.quantity')
            ->orderBy('products.name')
            ->limit(15)
            ->get([
                'branch_stocks.id',
                'branch_stocks.branch_id',
                'branch_stocks.quantity',
                'branches.name AS branch_name',
                'products.code AS product_code',
                'products.name AS product_name',
                'products.minimum_stock',
                'units.name AS unit_name',
            ])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'branch' => (string) $row->branch_name,
                'product_code' => (string) $row->product_code,
                'product_name' => (string) $row->product_name,
                'quantity' => $this->quantity((string) $row->quantity),
                'quantity_value' => (string) $row->quantity,
                'unit' => (string) $row->unit_name,
                'minimum_stock' => $this->quantity((string) $row->minimum_stock),
                'minimum_stock_value' => (string) $row->minimum_stock,
                'status' => (float) $row->quantity <= 0 ? 'Habis' : 'Menipis',
                'detail_url' => Route::has('stocks.show')
                    ? route('stocks.show', $row->id)
                    : '',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function latestTransactions(
        ?Branch $branch,
        OwnerDashboardDateRange $range,
    ): array {
        return Sale::query()
            ->when($branch, fn (Builder $query): Builder => $query->where(
                'branch_id',
                $branch->getKey(),
            ))
            ->whereBetween('transaction_date', [$range->start, $range->end])
            ->with([
                'branch:id,name',
                'cashier:id,name',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(10)
            ->get([
                'id',
                'branch_id',
                'cashier_id',
                'invoice_number',
                'transaction_date',
                'total',
                'payment_method_name',
                'status',
            ])
            ->map(fn (Sale $sale): array => [
                'invoice_number' => $sale->invoice_number,
                'transaction_date' => $sale->transaction_date->translatedFormat('d M Y, H.i'),
                'transaction_date_iso' => $sale->transaction_date->toIso8601String(),
                'branch' => $sale->branch?->name ?? 'Cabang tidak tersedia',
                'cashier' => $sale->cashier?->name ?? 'Pengguna tidak tersedia',
                'total' => $sale->total,
                'total_formatted' => Rupiah::format($sale->total),
                'payment_method' => $sale->payment_method_name ?: 'Tidak Diketahui',
                'status' => $sale->statusLabel(),
                'status_variant' => $sale->statusBadgeVariant(),
                'detail_url' => route('sales.show', $sale->getKey()),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function latestExpenses(
        ?Branch $branch,
        OwnerDashboardDateRange $range,
    ): array {
        return Expense::query()
            ->when($branch, fn (Builder $query): Builder => $query->where(
                'branch_id',
                $branch->getKey(),
            ))
            ->where('expense_date', '>=', $range->start->toDateString())
            ->where('expense_date', '<', $range->end->addDay()->toDateString())
            ->with([
                'branch:id,name',
                'expenseCategory:id,name',
                'creator:id,name',
            ])
            ->latest('expense_date')
            ->latest('id')
            ->limit(10)
            ->get([
                'id',
                'branch_id',
                'expense_category_id',
                'created_by',
                'expense_date',
                'description',
                'amount',
                'status',
            ])
            ->map(fn (Expense $expense): array => [
                'expense_date' => $expense->expense_date->translatedFormat('d M Y'),
                'expense_date_iso' => $expense->expense_date->toDateString(),
                'branch' => $expense->branch?->name ?? 'Cabang tidak tersedia',
                'category' => $expense->expenseCategory?->name ?? 'Tanpa kategori',
                'description' => $expense->description,
                'amount' => $expense->amount,
                'amount_formatted' => Rupiah::format($expense->amount),
                'creator' => $expense->creator?->name ?? 'Pengguna tidak tersedia',
                'status' => $expense->statusLabel(),
                'status_variant' => match ($expense->status) {
                    Expense::STATUS_APPROVED => 'success',
                    Expense::STATUS_REJECTED => 'danger',
                    default => 'warning',
                },
                'detail_url' => route('expenses.show', $expense->getKey()),
            ])
            ->all();
    }

    private function quantity(string $value): string
    {
        return Quantity::format($value);
    }
}
