<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Unit;
use Tests\Feature\Product\ProductTestCase;

class ProductMinimumStockSettingTest extends ProductTestCase
{
    public function test_create_form_and_backend_use_default_only_for_new_product(): void
    {
        $owner = $this->createUser('owner');
        Setting::query()->create([
            'key' => 'business.default_minimum_stock',
            'value' => '7.500',
            'type' => 'decimal',
            'group' => 'business',
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $unit = Unit::factory()->create(['is_active' => true]);
        $oldProduct = Product::factory()->create([
            'category_id' => $category,
            'unit_id' => $unit,
            'minimum_stock' => '2.000',
        ]);

        $this->actingAs($owner)->get(route('products.create'))->assertSee('value="7,5"', false);
        $payload = $this->productPayload($owner, $category, $unit);
        unset($payload['minimum_stock']);
        $this->actingAs($owner)->post(route('products.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('products', ['code' => 'PRD-TEST-01', 'minimum_stock' => '7.500']);
        $this->assertSame('2.000', $oldProduct->refresh()->minimum_stock);
    }
}
