<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Unit;
use App\Services\Product\ProductService;

class ActivityLogProductTest extends ActivityLogTestCase
{
    public function test_product_and_price_changes_are_audited_with_changed_fields_only(): void
    {
        $owner = $this->user('owner');
        $category = Category::factory()->create(['is_active' => true]);
        $unit = Unit::factory()->create(['is_active' => true]);
        $service = app(ProductService::class);
        $data = [
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'code' => 'AUD-PROD-01',
            'barcode' => null,
            'name' => 'Produk Audit',
            'brand' => null,
            'size' => '1 kg',
            'purchase_price' => '10000.00',
            'selling_price' => '15000.00',
            'minimum_stock' => '2.000',
        ];

        $this->actingAs($owner);
        $product = $service->create($data, $owner);
        $service->update($product, [
            ...$data,
            'name' => 'Produk Audit Baru',
            'selling_price' => '17500.00',
            'price_change_reason' => 'Penyesuaian harga uji',
        ], $owner);

        $this->assertDatabaseHas('activity_logs', ['action' => 'product_created', 'reference_id' => $product->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_updated', 'reference_id' => $product->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product_selling_price_changed', 'reference_id' => $product->id]);
        $this->assertSame(1, ActivityLog::query()->where('action', 'product_selling_price_changed')->count());
    }
}
