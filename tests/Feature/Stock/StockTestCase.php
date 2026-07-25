<?php

namespace Tests\Feature\Stock;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StockTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createBranch(string $code = 'CBG-A', array $attributes = []): Branch
    {
        return Branch::factory()->create([
            'code' => $code,
            'name' => "Cabang {$code}",
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createUser(string $roleSlug, ?Branch $branch = null, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => ucfirst($roleSlug),
                'description' => null,
                'is_active' => true,
            ],
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
            'minimum_stock' => '5.000',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createStock(
        Branch $branch,
        Product $product,
        string $quantity = '10.000',
        string $averageCost = '10000.00',
    ): BranchStock {
        return BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'average_cost' => $averageCost,
        ]);
    }

    protected function createMovement(
        Branch $branch,
        Product $product,
        User $actor,
        string $type = StockMovement::TYPE_INITIAL,
        array $attributes = [],
    ): StockMovement {
        return StockMovement::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'created_by' => $actor->id,
            'movement_type' => $type,
            'reference_type' => null,
            'reference_id' => null,
            'quantity_before' => '0.000',
            'quantity_change' => '10.000',
            'quantity_after' => '10.000',
            'unit_cost' => '10000.00',
            'notes' => 'Stok awal toko',
            ...$attributes,
        ]);
    }

    protected function initialPayload(
        Branch $branch,
        Product $product,
        array $overrides = [],
    ): array {
        return [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '10.000',
            'reason' => 'Stok awal toko',
            ...$overrides,
        ];
    }
}
