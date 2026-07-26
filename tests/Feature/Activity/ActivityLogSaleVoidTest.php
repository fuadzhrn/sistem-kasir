<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use Tests\Feature\SaleVoid\SaleVoidTestCase;

class ActivityLogSaleVoidTest extends SaleVoidTestCase
{
    public function test_direct_void_creates_exactly_one_final_audit_log(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        ['sale' => $sale] = $this->createVoidableSale($branch, $owner);

        $this->actingAs($owner)->patch(route('sales.void', $sale), $this->voidPayload())->assertRedirect();

        $this->assertSame(1, ActivityLog::query()->where('action', 'sale_voided')->count());
        $this->assertSame(0, ActivityLog::query()->whereIn('action', [
            'sale_void_requested',
            'sale_void_approved',
            'sale_void_rejected',
        ])->count());
    }
}
