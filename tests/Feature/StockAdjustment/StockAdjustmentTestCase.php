<?php

namespace Tests\Feature\StockAdjustment;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StockAdjustmentTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createBranch(string $code = 'UTM', array $attributes = []): Branch
    {
        return Branch::factory()->create([
            'code' => $code,
            'name' => 'Cabang '.$code,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createUser(string $roleSlug, ?Branch $branch = null, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst($roleSlug), 'description' => null, 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch?->id,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createProduct(array $attributes = []): Product
    {
        return Product::factory()->create([
            'category_id' => $attributes['category_id'] ?? Category::factory(),
            'unit_id' => $attributes['unit_id'] ?? Unit::factory(),
            'purchase_price' => '10000.00',
            'selling_price' => '15000.00',
            'minimum_stock' => '5.000',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createStock(
        Branch $branch,
        Product $product,
        string $quantity = '10.000',
        string $averageCost = '50000.00',
    ): BranchStock {
        return BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'average_cost' => $averageCost,
        ]);
    }

    protected function createAdjustment(
        Branch $branch,
        Product $product,
        User $creator,
        array $attributes = [],
    ): StockAdjustment {
        $adjustment = StockAdjustment::query()->create([
            'adjustment_number' => $attributes['adjustment_number'] ?? 'ADJ-'.$branch->code.'-20260725-0001',
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'adjustment_type' => $attributes['adjustment_type'] ?? StockAdjustment::TYPE_ADDITION,
            'quantity' => $attributes['quantity'] ?? '2.000',
            'target_quantity' => $attributes['target_quantity'] ?? null,
            'quantity_before' => $attributes['quantity_before'] ?? '10.000',
            'quantity_change' => $attributes['quantity_change'] ?? '2.000',
            'quantity_after' => $attributes['quantity_after'] ?? '12.000',
            'unit_cost' => $attributes['unit_cost'] ?? '50000.00',
            'reason' => $attributes['reason'] ?? 'Alasan penyesuaian untuk pengujian.',
            'created_by' => $creator->id,
        ]);

        if (isset($attributes['created_at'])) {
            $adjustment->forceFill([
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['created_at'],
            ])->save();
        }

        return $adjustment;
    }

    protected function payload(Branch $branch, Product $product, array $overrides = []): array
    {
        return [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'adjustment_type' => StockAdjustment::TYPE_ADDITION,
            'quantity' => '2.000',
            'reason' => 'Produk contoh promosi belum tercatat pada penerimaan.',
            ...$overrides,
        ];
    }
}
