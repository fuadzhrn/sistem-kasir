<?php

namespace Tests\Feature\Demo;

use App\Models\ActivityLog;

class DemoDataActivityLogTest extends DemoDataTestCase
{
    public function test_required_demo_activities_are_available(): void
    {
        $this->seedDemo();

        foreach ([
            'product_created',
            'initial_stock_created',
            'stock_receipt_created',
            'sale_created',
            'sale_voided',
            'receipt_reprint_requested',
            'expense_created',
            'store_settings_updated',
            'login_failed',
        ] as $action) {
            $this->assertTrue(ActivityLog::query()->where('action', $action)->exists(), $action);
        }
    }
}
