<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Unit;
use App\Services\Product\ProductService;
use RuntimeException;

class ActivityLogTransactionTest extends ActivityLogTestCase
{
    public function test_business_change_rolls_back_when_transactional_audit_fails(): void
    {
        $owner = $this->user('owner');
        $category = Category::factory()->create(['is_active' => true]);
        $unit = Unit::factory()->create(['is_active' => true]);
        ActivityLog::creating(function (): never {
            throw new RuntimeException('Audit sengaja gagal.');
        });

        $this->actingAs($owner);

        try {
            app(ProductService::class)->create([
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'code' => 'ROLLBACK-AUDIT',
                'name' => 'Produk rollback',
                'purchase_price' => '10000.00',
                'selling_price' => '15000.00',
                'minimum_stock' => '1.000',
            ], $owner);
            $this->fail('Audit yang gagal seharusnya membatalkan transaksi.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit sengaja gagal.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('products', ['code' => 'ROLLBACK-AUDIT']);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
