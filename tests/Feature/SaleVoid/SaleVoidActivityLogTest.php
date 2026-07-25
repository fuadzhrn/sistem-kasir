<?php

namespace Tests\Feature\SaleVoid;

use App\Models\ActivityLog;
use App\Models\Sale;

class SaleVoidActivityLogTest extends SaleVoidTestCase
{
    public function test_successful_void_creates_one_safe_activity_log(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        ['sale' => $sale] = $this->createVoidableSale($branch, $owner);

        $this->actingAs($owner)->patch(route('sales.void', $sale), $this->voidPayload());

        $log = ActivityLog::query()->where('action', 'sale_voided')->firstOrFail();
        $this->assertSame('sales', $log->module);
        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame($branch->id, $log->branch_id);
        $this->assertSame(Sale::class, $log->reference_type);
        $this->assertSame($sale->id, $log->reference_id);
        $this->assertStringContainsString($sale->invoice_number, $log->description);
        $this->assertStringContainsString('Rp180.000', $log->description);
        $this->assertStringNotContainsString('checkout', mb_strtolower($log->description));
        $this->assertStringNotContainsString('password', mb_strtolower($log->description));
    }
}
