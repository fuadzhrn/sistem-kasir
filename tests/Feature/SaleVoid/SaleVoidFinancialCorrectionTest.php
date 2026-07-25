<?php

namespace Tests\Feature\SaleVoid;

use App\Models\Sale;

class SaleVoidFinancialCorrectionTest extends SaleVoidTestCase
{
    public function test_voided_sale_is_excluded_from_active_financial_totals_without_zeroing_history(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        ['sale' => $sale] = $this->createVoidableSale($branch, $cashier);

        $this->assertSame('180000.00', number_format(
            (float) Sale::query()->financiallyActive()->sum('total'),
            2,
            '.',
            '',
        ));
        $this->actingAs($cashier)->patch(route('sales.void', $sale), $this->voidPayload());

        $sale->refresh();
        $this->assertSame('0', (string) Sale::query()->financiallyActive()->sum('total'));
        $this->assertSame(0, Sale::query()->financiallyActive()->count());
        $this->assertSame(1, Sale::query()->voided()->count());
        $this->assertSame('180000.00', $sale->total);
        $this->assertSame('100000.00', $sale->total_cost);
        $this->assertSame('80000.00', $sale->gross_profit);
        $this->assertDatabaseHas('sale_voids', [
            'sale_id' => $sale->id,
            'original_total' => '180000.00',
            'original_total_cost' => '100000.00',
            'original_gross_profit' => '80000.00',
        ]);
    }

    public function test_history_summary_uses_only_completed_sales_for_active_revenue(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        ['sale' => $sale] = $this->createVoidableSale($branch, $owner);
        $this->actingAs($owner)->patch(route('sales.void', $sale), $this->voidPayload());

        $this->actingAs($owner)->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Transaksi aktif')
            ->assertSee('Rp0')
            ->assertSee($sale->invoice_number);
    }
}
