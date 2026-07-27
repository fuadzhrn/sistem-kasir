<?php

namespace Tests\Feature\Demo;

use App\Models\Sale;
use App\Models\SaleVoid;
use App\Models\StockMovement;

class DemoDataSaleVoidIntegrityTest extends DemoDataTestCase
{
    public function test_voids_are_direct_and_restore_stock_once(): void
    {
        $this->seedDemo();

        $void = SaleVoid::query()->sole();
        $this->assertSame(Sale::STATUS_VOIDED, $void->sale->status);
        $this->assertNull($void->reviewed_by);
        $this->assertNull($void->reviewed_at);
        $this->assertTrue(
            StockMovement::query()
                ->where('movement_type', 'void_sale')
                ->where('reference_id', $void->id)
                ->exists(),
        );
    }
}
