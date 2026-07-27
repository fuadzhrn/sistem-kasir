<?php

namespace Tests\Feature\Demo;

use App\Models\Sale;

class DemoDataSaleIntegrityTest extends DemoDataTestCase
{
    public function test_sales_have_items_unique_numbers_and_consistent_totals(): void
    {
        $this->seedDemo();

        $sales = Sale::query()->with('items')->get();
        $this->assertCount(20, $sales);
        $this->assertCount(20, $sales->pluck('invoice_number')->unique());

        foreach ($sales as $sale) {
            $this->assertNotEmpty($sale->items);
            $this->assertEqualsWithDelta(
                (float) $sale->total,
                $sale->items->sum(fn ($item): float => (float) $item->subtotal),
                0.02,
            );
        }
    }
}
