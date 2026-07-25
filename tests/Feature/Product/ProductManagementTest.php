<?php

namespace Tests\Feature\Product;

use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Unit;

class ProductManagementTest extends ProductTestCase
{
    public function test_owner_creates_normalized_product_without_stock_or_initial_history(): void
    {
        $owner = $this->createUser('owner');
        $stockCount = BranchStock::query()->count();
        $this->actingAs($owner)->post(route('products.store'), $this->productPayload($owner, attributes: [
            'code' => '  pst_001 ',
            'barcode' => '001234567890',
            'name' => '  Pestisida   ABC ',
            'is_active' => false,
            'created_by' => 999,
            'updated_by' => 999,
            'image_path' => 'unsafe.php',
            'branch_id' => 999,
        ]))->assertRedirect();

        $product = Product::query()->where('code', 'PST_001')->firstOrFail();
        $this->assertSame('001234567890', $product->barcode);
        $this->assertSame('Pestisida ABC', $product->name);
        $this->assertSame($owner->id, $product->created_by);
        $this->assertSame($owner->id, $product->updated_by);
        $this->assertTrue($product->is_active);
        $this->assertSame($stockCount, BranchStock::query()->count());
        $this->assertSame(0, PriceHistory::query()->count());
        $this->assertNull($product->image_path);
    }

    public function test_admin_creates_product_with_zero_purchase_price_and_cannot_submit_it(): void
    {
        $admin = $this->createUser('admin');
        $payload = $this->productPayload($admin);
        $this->actingAs($admin)->post(route('products.store'), [
            ...$payload,
            'purchase_price' => '999999.00',
        ])->assertSessionHasErrors('purchase_price');
        $this->assertDatabaseMissing('products', ['code' => $payload['code']]);

        $this->actingAs($admin)->post(route('products.store'), $payload)->assertRedirect();
        $product = Product::query()->where('code', $payload['code'])->firstOrFail();
        $this->assertSame('0.00', $product->purchase_price);
    }

    public function test_create_validation_rejects_inactive_master_duplicate_and_invalid_values(): void
    {
        $owner = $this->createUser('owner');
        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        $inactiveUnit = Unit::factory()->create(['is_active' => false]);
        Product::factory()->create(['code' => 'DUPLIKAT', 'barcode' => '000111222']);

        $this->actingAs($owner)->post(route('products.store'), $this->productPayload(
            $owner,
            $inactiveCategory,
            $inactiveUnit,
            [
                'code' => 'duplikat',
                'barcode' => '000111222',
                'selling_price' => '-1',
                'minimum_stock' => '-1',
            ],
        ))->assertSessionHasErrors(['category_id', 'unit_id', 'code', 'barcode', 'selling_price', 'minimum_stock']);

        $this->actingAs($owner)->post(route('products.store'), $this->productPayload(
            $owner,
            attributes: ['code' => 'KODE DENGAN SPASI', 'name' => ''],
        ))->assertSessionHasErrors(['code', 'name']);
    }

    public function test_owner_and_admin_edit_product_with_master_data_rules(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        $inactiveUnit = Unit::factory()->create(['is_active' => false]);
        $otherInactiveCategory = Category::factory()->create(['is_active' => false]);
        $product = Product::factory()->create([
            'category_id' => $inactiveCategory,
            'unit_id' => $inactiveUnit,
            'purchase_price' => '50000.00',
            'selling_price' => '75000.00',
        ]);

        $this->actingAs($admin)->put(route('products.update', $product), $this->productPayload(
            $admin,
            $inactiveCategory,
            $inactiveUnit,
            ['code' => $product->code, 'barcode' => $product->barcode, 'name' => 'Identitas Baru'],
        ))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Identitas Baru']);
        $this->assertSame(0, PriceHistory::query()->count());

        $this->actingAs($admin)->put(route('products.update', $product), $this->productPayload(
            $admin,
            $otherInactiveCategory,
            $inactiveUnit,
            ['code' => $product->code, 'barcode' => $product->barcode],
        ))->assertSessionHasErrors('category_id');

        $this->actingAs($owner)->put(route('products.update', $product), $this->productPayload(
            $owner,
            $inactiveCategory,
            $inactiveUnit,
            ['code' => $product->code, 'barcode' => $product->barcode, 'brand' => 'Merek Owner'],
        ))->assertRedirect();
        $this->assertSame($owner->id, $product->fresh()->updated_by);
    }
}
