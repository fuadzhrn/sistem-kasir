<?php

namespace Tests\Feature\Product;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Storage;

class ProductStatusTest extends ProductTestCase
{
    public function test_owner_and_admin_can_deactivate_without_changing_related_data(): void
    {
        Storage::fake('public');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        Storage::disk('public')->put('products/status.png', 'image');
        $product = Product::factory()->create(['image_path' => 'products/status.png']);
        $stock = BranchStock::query()->create([
            'branch_id' => Branch::factory()->create()->id,
            'product_id' => $product->id,
            'quantity' => '12.500',
            'average_cost' => '50000.00',
        ]);
        $history = PriceHistory::factory()->create(['product_id' => $product, 'changed_by' => $owner]);

        $this->actingAs($admin)->patch(route('products.status.update', $product), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($product->fresh()->is_active);
        $this->assertSame('12.500', $stock->fresh()->quantity);
        $this->assertModelExists($history);
        Storage::disk('public')->assertExists('products/status.png');

        $this->actingAs($owner)->patch(route('products.status.update', $product), ['is_active' => true])
            ->assertRedirect();
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_product_cannot_be_activated_with_inactive_category_or_unit(): void
    {
        $owner = $this->createUser('owner');
        $category = Category::factory()->create(['is_active' => false]);
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category,
            'unit_id' => $unit,
            'is_active' => false,
        ]);
        $this->actingAs($owner)->patch(route('products.status.update', $product), ['is_active' => true])
            ->assertSessionHasErrors('is_active');

        $category->update(['is_active' => true]);
        $unit->update(['is_active' => false]);
        $this->actingAs($owner)->patch(route('products.status.update', $product), ['is_active' => true])
            ->assertSessionHasErrors('is_active');
        $this->assertFalse($product->fresh()->is_active);
    }
}
