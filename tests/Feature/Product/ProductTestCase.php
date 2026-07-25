<?php

namespace Tests\Feature\Product;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ProductTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role, array $attributes = []): User
    {
        $roleModel = Role::query()->firstOrCreate(
            ['slug' => $role],
            ['name' => ucfirst($role), 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $roleModel,
            'branch_id' => $role === 'owner' ? null : Branch::factory()->create(),
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function productPayload(
        User $actor,
        ?Category $category = null,
        ?Unit $unit = null,
        array $attributes = [],
    ): array {
        $payload = [
            'category_id' => ($category ?? Category::factory()->create())->getKey(),
            'unit_id' => ($unit ?? Unit::factory()->create())->getKey(),
            'code' => 'PRD-TEST-01',
            'barcode' => '001234567890',
            'name' => 'Produk Test',
            'brand' => 'Merek Test',
            'size' => '500 ml',
            'selling_price' => '75000.00',
            'minimum_stock' => '5.000',
        ];

        if ($actor->isOwner()) {
            $payload['purchase_price'] = '50000.00';
        }

        return [...$payload, ...$attributes];
    }
}
