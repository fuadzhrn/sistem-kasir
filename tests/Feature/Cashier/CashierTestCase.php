<?php

namespace Tests\Feature\Cashier;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CashierTestCase extends TestCase
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

    protected function createCategory(array $attributes = []): Category
    {
        return Category::factory()->create([
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createUnit(array $attributes = []): Unit
    {
        return Unit::factory()->create([
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createProduct(array $attributes = []): Product
    {
        return Product::factory()->create([
            'category_id' => $attributes['category_id'] ?? $this->createCategory(),
            'unit_id' => $attributes['unit_id'] ?? $this->createUnit(),
            'purchase_price' => '12500.00',
            'selling_price' => '20000.00',
            'minimum_stock' => '5.000',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createStock(
        Branch $branch,
        Product $product,
        string $quantity = '10.000',
        string $averageCost = '12500.00',
    ): BranchStock {
        return BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'average_cost' => $averageCost,
        ]);
    }

    protected function createPaymentMethod(array $attributes = []): PaymentMethod
    {
        return PaymentMethod::query()->create([
            'code' => $attributes['code'] ?? 'CASH',
            'name' => $attributes['name'] ?? 'Tunai',
            'type' => $attributes['type'] ?? 'cash',
            'is_active' => $attributes['is_active'] ?? true,
            'sort_order' => $attributes['sort_order'] ?? 1,
        ]);
    }

    protected function endpointParams(User $user, Branch $branch, array $overrides = []): array
    {
        return [
            ...($user->isOwner() ? ['branch_id' => $branch->id] : []),
            ...$overrides,
        ];
    }
}
