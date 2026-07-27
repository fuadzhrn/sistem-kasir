<?php

namespace Tests\Feature\Demo;

use App\Models\BranchStock;
use App\Models\StockMovement;

class DemoDataStockIntegrityTest extends DemoDataTestCase
{
    public function test_stock_is_created_through_auditable_movements_and_never_negative(): void
    {
        $this->seedDemo();

        $this->assertFalse(BranchStock::query()->where('quantity', '<', 0)->exists());
        $this->assertTrue(StockMovement::query()->where('movement_type', 'initial')->exists());
        $this->assertTrue(StockMovement::query()->where('movement_type', 'purchase')->exists());
        $this->assertTrue(StockMovement::query()->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])->exists());
        $this->assertTrue(BranchStock::query()->where('quantity', 0)->exists());
    }
}
