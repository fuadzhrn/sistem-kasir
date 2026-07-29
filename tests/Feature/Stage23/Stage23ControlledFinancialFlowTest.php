<?php

namespace Tests\Feature\Stage23;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockReceiptItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Sale\SaleTestCase;

class Stage23ControlledFinancialFlowTest extends SaleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-07-25 14:35:00', 'Asia/Jakarta'),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_two_branch_manual_financial_scenario_matches_owner_dashboard_and_charts(): void
    {
        $owner = $this->createUser('owner');
        $branchA = $this->createBranch('T23A', ['name' => 'Cabang A']);
        $branchB = $this->createBranch('T23B', ['name' => 'Cabang B']);
        $productA = $this->createProduct([
            'code' => 'T23-PROD-A',
            'name' => 'Produk Tahap 23 A',
            'selling_price' => '20000.00',
        ]);
        $productB = $this->createProduct([
            'code' => 'T23-PROD-B',
            'name' => 'Produk Tahap 23 B',
            'selling_price' => '25000.00',
        ]);
        $stockA = $this->createStock($branchA, $productA, '10.000', '11000.00');
        $stockB = $this->createStock($branchB, $productB, '10.000', '15000.00');
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), $this->payload(
                $owner,
                $branchA,
                $productA,
                $payment,
                [
                    'items' => [['product_id' => $productA->id, 'quantity' => '5.000']],
                    'discount_amount' => '10000',
                    'amount_received' => '100000',
                    'expected_subtotal' => '100000.00',
                    'expected_total' => '90000.00',
                ],
            ))
            ->assertCreated();

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), $this->payload(
                $owner,
                $branchB,
                $productB,
                $payment,
                [
                    'items' => [['product_id' => $productB->id, 'quantity' => '3.000']],
                    'discount_amount' => '0',
                    'amount_received' => '75000',
                    'expected_subtotal' => '75000.00',
                    'expected_total' => '75000.00',
                ],
            ))
            ->assertCreated();

        $category = ExpenseCategory::factory()->create([
            'name' => 'Operasional Tahap 23',
            'slug' => 'operasional-tahap-23',
            'created_by' => $owner->id,
        ]);
        $this->createControlledExpense($branchA, $owner, $category, Expense::STATUS_APPROVED, '15000.00');
        $this->createControlledExpense($branchA, $owner, $category, Expense::STATUS_PENDING, '7000.00');
        $this->createControlledExpense($branchA, $owner, $category, Expense::STATUS_REJECTED, '5000.00');
        $this->createControlledExpense($branchB, $owner, $category, Expense::STATUS_APPROVED, '5000.00');

        $response = $this->actingAs($owner)
            ->getJson(route('dashboard.owner.data', ['period' => 'this_month']))
            ->assertOk()
            ->assertJsonPath('data.cards.gross_sales.value', '175000.00')
            ->assertJsonPath('data.cards.net_sales.value', '165000.00')
            ->assertJsonPath('data.cards.cost_of_goods_sold.value', '100000.00')
            ->assertJsonPath('data.cards.gross_profit.value', '65000.00')
            ->assertJsonPath('data.cards.approved_expenses.value', '20000.00')
            ->assertJsonPath('data.cards.net_profit.value', '45000.00')
            ->assertJsonPath('data.cards.receipt_count.value', 2)
            ->assertJsonPath('data.cards.net_sales.formatted', 'Rp165.000')
            ->assertJsonPath('data.cards.net_profit.formatted', 'Rp45.000')
            ->assertJsonPath('data.charts.branch_comparison.labels', ['Cabang A', 'Cabang B'])
            ->assertJsonPath('data.charts.branch_comparison.net_sales', [90000, 75000])
            ->assertJsonPath('data.charts.branch_comparison.net_profit', [20000, 25000]);

        $this->assertSame(
            175000,
            array_sum($response->json('data.charts.sales_trend.gross_sales')),
        );
        $this->assertSame(
            165000,
            array_sum($response->json('data.charts.sales_trend.net_sales')),
        );
        $this->assertSame(
            65000,
            array_sum($response->json('data.charts.profit_trend.gross_profit')),
        );
        $this->assertSame(
            45000,
            array_sum($response->json('data.charts.profit_trend.net_profit')),
        );
        $this->assertSame(
            165000,
            array_sum($response->json('data.charts.payment_composition.values')),
        );

        $this->actingAs($owner)
            ->getJson(route('dashboard.owner.data', [
                'period' => 'this_month',
                'branch_id' => $branchA->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.cards.gross_sales.value', '100000.00')
            ->assertJsonPath('data.cards.net_sales.value', '90000.00')
            ->assertJsonPath('data.cards.cost_of_goods_sold.value', '55000.00')
            ->assertJsonPath('data.cards.gross_profit.value', '35000.00')
            ->assertJsonPath('data.cards.approved_expenses.value', '15000.00')
            ->assertJsonPath('data.cards.net_profit.value', '20000.00')
            ->assertJsonPath('data.cards.receipt_count.value', 1);

        $this->actingAs($owner)
            ->getJson(route('dashboard.owner.data', [
                'period' => 'this_month',
                'branch_id' => $branchB->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.cards.gross_sales.value', '75000.00')
            ->assertJsonPath('data.cards.net_sales.value', '75000.00')
            ->assertJsonPath('data.cards.cost_of_goods_sold.value', '45000.00')
            ->assertJsonPath('data.cards.gross_profit.value', '30000.00')
            ->assertJsonPath('data.cards.approved_expenses.value', '5000.00')
            ->assertJsonPath('data.cards.net_profit.value', '25000.00')
            ->assertJsonPath('data.cards.receipt_count.value', 1);

        $this->assertSame('5.000', $stockA->refresh()->quantity);
        $this->assertSame('7.000', $stockB->refresh()->quantity);
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('sale_items', 2);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertSame(0, $this->orphanSaleItemCount());
        $this->assertSame(0, $this->negativeStockCount());
        $this->assertSame(2, Sale::query()->distinct()->count('invoice_number'));
    }

    public function test_weighted_average_cost_example_and_sale_snapshot_remain_exact(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('T23HPP');
        $product = $this->createProduct([
            'code' => 'T23-HPP',
            'selling_price' => '20000.00',
            'purchase_price' => '10000.00',
        ]);
        $stock = $this->createStock($branch, $product, '10.000', '10000.00');
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)
            ->post(route('stock-receipts.store'), [
                'branch_id' => $branch->id,
                'receipt_date' => now()->toDateString(),
                'supplier_name' => 'Supplier Tahap 23',
                'notes' => 'Pengujian HPP rata-rata Tahap 23',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => '10.000',
                    'purchase_price' => '12000',
                ]],
            ])
            ->assertRedirect();

        $this->assertSame('20.000', $stock->refresh()->quantity);
        $this->assertSame('11000.00', $stock->average_cost);
        $this->assertSame('120000.00', StockReceiptItem::query()->sole()->subtotal);

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), $this->payload(
                $owner,
                $branch,
                $product,
                $payment,
                [
                    'items' => [['product_id' => $product->id, 'quantity' => '5.000']],
                    'amount_received' => '100000',
                    'expected_subtotal' => '100000.00',
                    'expected_total' => '100000.00',
                ],
            ))
            ->assertCreated();

        $sale = Sale::query()->sole();
        $saleItem = SaleItem::query()->sole();
        $this->assertSame('55000.00', $sale->total_cost);
        $this->assertSame('11000.00', $saleItem->cost_price);
        $this->assertSame('55000.00', number_format(
            (float) $saleItem->quantity * (float) $saleItem->cost_price,
            2,
            '.',
            '',
        ));
        $this->assertSame('15.000', $stock->refresh()->quantity);
        $this->assertSame('11000.00', $stock->average_cost);

        $this->actingAs($owner)
            ->post(route('stock-receipts.store'), [
                'branch_id' => $branch->id,
                'receipt_date' => now()->toDateString(),
                'supplier_name' => 'Supplier Tahap 23 Kedua',
                'notes' => 'Memastikan snapshot transaksi tidak berubah',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => '5.000',
                    'purchase_price' => '15000',
                ]],
            ])
            ->assertRedirect();

        $this->assertSame('20.000', $stock->refresh()->quantity);
        $this->assertSame('12000.00', $stock->average_cost);
        $this->assertSame('55000.00', $sale->refresh()->total_cost);
        $this->assertSame('11000.00', $saleItem->refresh()->cost_price);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertDatabaseCount('activity_logs', 3);
        $this->assertSame(0, $this->negativeStockCount());
    }

    public function test_competing_checkouts_never_make_stock_negative_and_exact_fit_succeeds(): void
    {
        $branch = $this->createBranch('T23RACE');
        $cashierA = $this->createUser('cashier', $branch);
        $cashierB = $this->createUser('cashier', $branch);
        $payment = $this->createPaymentMethod();
        $scarceProduct = $this->createProduct([
            'code' => 'T23-SCARCE',
            'selling_price' => '10000.00',
        ]);
        $scarceStock = $this->createStock($branch, $scarceProduct, '5.000', '5000.00');

        $this->actingAs($cashierA)
            ->postJson(route('cashier.checkout.store'), $this->payload(
                $cashierA,
                $branch,
                $scarceProduct,
                $payment,
                [
                    'items' => [['product_id' => $scarceProduct->id, 'quantity' => '4.000']],
                    'amount_received' => '40000',
                    'expected_subtotal' => '40000.00',
                    'expected_total' => '40000.00',
                ],
            ))
            ->assertCreated();

        $this->actingAs($cashierB)
            ->postJson(route('cashier.checkout.store'), $this->payload(
                $cashierB,
                $branch,
                $scarceProduct,
                $payment,
                [
                    'items' => [['product_id' => $scarceProduct->id, 'quantity' => '4.000']],
                    'amount_received' => '40000',
                    'expected_subtotal' => '40000.00',
                    'expected_total' => '40000.00',
                ],
            ))
            ->assertConflict()
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

        $this->assertSame('1.000', $scarceStock->refresh()->quantity);

        $exactProduct = $this->createProduct([
            'code' => 'T23-EXACT',
            'selling_price' => '10000.00',
        ]);
        $exactStock = $this->createStock($branch, $exactProduct, '10.000', '5000.00');

        foreach ([[$cashierA, '4.000'], [$cashierB, '6.000']] as [$cashier, $quantity]) {
            $total = (string) ((int) $quantity * 10000);

            $this->actingAs($cashier)
                ->postJson(route('cashier.checkout.store'), $this->payload(
                    $cashier,
                    $branch,
                    $exactProduct,
                    $payment,
                    [
                        'items' => [[
                            'product_id' => $exactProduct->id,
                            'quantity' => $quantity,
                        ]],
                        'amount_received' => $total,
                        'expected_subtotal' => $total.'.00',
                        'expected_total' => $total.'.00',
                    ],
                ))
                ->assertCreated();
        }

        $this->assertSame('0.000', $exactStock->refresh()->quantity);
        $this->assertSame(0, $this->negativeStockCount());
        $this->assertDatabaseCount('sales', 3);
        $this->assertDatabaseCount('sale_items', 3);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertSame(
            3,
            DB::table('activity_logs')->where('action', 'sale_created')->count(),
        );
        $this->assertSame(0, $this->orphanSaleItemCount());
        $this->assertSame(3, Sale::query()->distinct()->count('invoice_number'));
    }

    private function createControlledExpense(
        Branch $branch,
        User $actor,
        ExpenseCategory $category,
        string $status,
        string $amount,
    ): Expense {
        return Expense::factory()->create([
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => $amount,
            'description' => 'Pengeluaran terkontrol Tahap 23',
            'status' => $status,
            'created_by' => $actor->id,
            'approved_by' => $status === Expense::STATUS_APPROVED ? $actor->id : null,
            'approved_at' => $status === Expense::STATUS_APPROVED ? now() : null,
            'rejected_by' => $status === Expense::STATUS_REJECTED ? $actor->id : null,
            'rejected_at' => $status === Expense::STATUS_REJECTED ? now() : null,
            'rejection_reason' => $status === Expense::STATUS_REJECTED
                ? 'Ditolak untuk skenario Tahap 23'
                : null,
        ]);
    }

    private function negativeStockCount(): int
    {
        return DB::table('branch_stocks')->where('quantity', '<', 0)->count();
    }

    private function orphanSaleItemCount(): int
    {
        return DB::table('sale_items')
            ->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.id')
            ->count();
    }
}
