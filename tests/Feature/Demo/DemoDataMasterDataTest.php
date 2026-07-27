<?php

namespace Tests\Feature\Demo;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Unit;

class DemoDataMasterDataTest extends DemoDataTestCase
{
    public function test_master_data_is_unique_active_and_complete(): void
    {
        $this->seedDemo();

        $this->assertGreaterThanOrEqual(13, Category::query()->where('is_active', true)->count());
        $this->assertGreaterThanOrEqual(13, Unit::query()->where('is_active', true)->count());
        $this->assertSame(
            ['CASH', 'QRIS', 'TRANSFER'],
            PaymentMethod::query()->whereIn('code', ['CASH', 'QRIS', 'TRANSFER'])->orderBy('code')->pluck('code')->all(),
        );
        $this->assertSame(0, Category::query()->select('slug')->groupBy('slug')->havingRaw('COUNT(*) > 1')->count());
    }
}
