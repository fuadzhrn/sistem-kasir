<?php

namespace Tests\Feature\Report;

use App\Models\Branch;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleVoid;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use Tests\Feature\OwnerDashboard\OwnerDashboardTestCase;

abstract class ReportTestCase extends OwnerDashboardTestCase
{
    /**
     * @return array<int, string>
     */
    protected function reportSlugs(): array
    {
        return [
            'sales',
            'receipts',
            'cost-of-goods-sold',
            'gross-profit',
            'net-profit',
            'expenses',
            'stocks',
            'stock-receipts',
            'stock-movements',
            'top-products',
            'branches',
            'cashiers',
            'price-histories',
            'sale-voids',
        ];
    }

    protected function getReport(User $user, string $slug, array $filters = [])
    {
        return $this->actingAs($user)->get(route("reports.{$slug}.index", [
            'period' => 'this_month',
            ...$filters,
        ]));
    }

    protected function getPrintReport(User $user, string $slug, array $filters = [])
    {
        return $this->actingAs($user)->get(route("reports.{$slug}.print", [
            'period' => 'this_month',
            ...$filters,
        ]));
    }

    protected function createStockReceipt(
        Branch $branch,
        User $creator,
        Product $product,
        array $attributes = [],
    ): StockReceipt {
        $receipt = StockReceipt::query()->create([
            'branch_id' => $branch->id,
            'receipt_number' => 'BM-'.$branch->code.'-20260725-0001',
            'receipt_date' => '2026-07-15',
            'supplier_name' => 'Supplier Laporan',
            'total_cost' => '60000.00',
            'notes' => 'Dokumen laporan',
            'created_by' => $creator->id,
            ...$attributes,
        ]);
        StockReceiptItem::query()->create([
            'stock_receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => '1.000',
            'purchase_price' => '60000.00',
            'subtotal' => '60000.00',
            'quantity_before' => '2.000',
            'quantity_after' => '3.000',
            'average_cost_before' => '50000.00',
            'average_cost_after' => '53333.33',
        ]);

        return $receipt;
    }

    protected function createStockMovement(
        Branch $branch,
        User $creator,
        Product $product,
        array $attributes = [],
    ): StockMovement {
        return StockMovement::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'created_by' => $creator->id,
            'movement_type' => StockMovement::TYPE_PURCHASE,
            'reference_type' => null,
            'reference_id' => null,
            'quantity_before' => '2.000',
            'quantity_change' => '1.000',
            'quantity_after' => '3.000',
            'unit_cost' => '60000.00',
            'notes' => 'Movement laporan',
            ...$attributes,
        ]);
    }

    protected function createPriceHistory(
        User $user,
        Product $product,
        array $attributes = [],
    ): PriceHistory {
        return PriceHistory::factory()->create([
            'product_id' => $product->id,
            'changed_by' => $user->id,
            'old_purchase_price' => '50000.00',
            'new_purchase_price' => '55000.00',
            'old_selling_price' => '75000.00',
            'new_selling_price' => '80000.00',
            'reason' => 'Penyesuaian laporan',
            'changed_at' => now(),
            ...$attributes,
        ]);
    }

    protected function createSaleVoid(
        Sale $sale,
        User $voider,
        array $attributes = [],
    ): SaleVoid {
        return SaleVoid::query()->create([
            'sale_id' => $sale->id,
            'branch_id' => $sale->branch_id,
            'requested_by' => $voider->id,
            'voided_by' => $voider->id,
            'voided_at' => now(),
            'reason' => 'Pembatalan laporan',
            'original_subtotal' => $sale->subtotal,
            'original_discount_amount' => $sale->discount_amount,
            'original_total' => $sale->total,
            'original_total_cost' => $sale->total_cost,
            'original_gross_profit' => $sale->gross_profit,
            'payment_method_name' => $sale->payment_method_name,
            'refund_confirmed' => true,
            'status' => Sale::STATUS_VOIDED,
            ...$attributes,
        ]);
    }
}
