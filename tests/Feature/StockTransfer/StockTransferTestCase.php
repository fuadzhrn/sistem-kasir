<?php

namespace Tests\Feature\StockTransfer;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StockTransferTestCase extends TestCase
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

    protected function createTransfer(
        Branch $source,
        Branch $destination,
        Product $product,
        User $requester,
        array $attributes = [],
    ): StockTransfer {
        $sequence = StockTransfer::query()->count() + 1;

        return StockTransfer::query()->create([
            'transfer_number' => $attributes['transfer_number']
                ?? 'TRF-'.$source->code.'-'.$destination->code.'-20260725-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'from_branch_id' => $source->id,
            'to_branch_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => $attributes['quantity'] ?? '2.000',
            'status' => $attributes['status'] ?? StockTransfer::STATUS_PENDING,
            'unit_cost' => $attributes['unit_cost'] ?? '0.00',
            'notes' => $attributes['notes'] ?? 'Permintaan pengiriman stok untuk kebutuhan cabang.',
            'requested_by' => $requester->id,
            ...$attributes,
        ]);
    }

    protected function payload(
        Branch $source,
        Branch $destination,
        Product $product,
        array $overrides = [],
    ): array {
        return [
            'from_branch_id' => $source->id,
            'to_branch_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => '2.000',
            'notes' => 'Pengiriman diperlukan untuk memenuhi kebutuhan cabang tujuan.',
            ...$overrides,
        ];
    }
}
