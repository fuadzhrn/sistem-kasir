<?php

namespace Tests\Feature\SaleVoid;

use App\Models\ActivityLog;
use App\Models\SaleVoid;
use App\Models\StockMovement;

class SaleVoidConcurrencyTest extends SaleVoidTestCase
{
    public function test_repeated_request_is_idempotent_and_does_not_restore_stock_twice(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        ['sale' => $sale, 'stock' => $stock] = $this->createVoidableSale($branch, $owner);
        $headers = ['Accept' => 'application/json'];

        $this->actingAs($owner)->withHeaders($headers)
            ->patchJson(route('sales.void', $sale), $this->voidPayload())
            ->assertOk()
            ->assertJsonPath('idempotent', false);
        $this->actingAs($owner)->withHeaders($headers)
            ->patchJson(route('sales.void', $sale), $this->voidPayload())
            ->assertOk()
            ->assertJsonPath('idempotent', true);

        $this->assertSame('12.000', $stock->fresh()->quantity);
        $this->assertSame(1, SaleVoid::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'void_sale')->count());
        $this->assertSame(1, ActivityLog::query()->where('action', 'sale_voided')->count());
    }

    public function test_unique_sale_id_is_declared_as_last_database_guard(): void
    {
        $migration = file_get_contents(database_path(
            'migrations/2026_07_25_002500_complete_direct_sale_void_workflow.php',
        ));

        $this->assertStringContainsString("unique('sale_id'", $migration);
    }
}
