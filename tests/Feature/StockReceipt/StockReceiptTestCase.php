<?php

namespace Tests\Feature\StockReceipt;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StockReceiptTestCase extends TestCase
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

    protected function createReceipt(
        Branch $branch,
        User $creator,
        Product $product,
        array $attributes = [],
    ): StockReceipt {
        $receipt = StockReceipt::query()->create([
            'branch_id' => $branch->id,
            'receipt_number' => $attributes['receipt_number'] ?? 'BM-'.$branch->code.'-20260725-0001',
            'receipt_date' => $attributes['receipt_date'] ?? '2026-07-25',
            'supplier_name' => $attributes['supplier_name'] ?? 'Supplier Uji',
            'total_cost' => $attributes['total_cost'] ?? '60000.00',
            'notes' => $attributes['notes'] ?? 'Dokumen uji',
            'created_by' => $creator->id,
        ]);

        StockReceiptItem::query()->create([
            'stock_receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity' => '1.000',
            'purchase_price' => '60000.00',
            'subtotal' => '60000.00',
            'quantity_before' => '10.000',
            'quantity_after' => '11.000',
            'average_cost_before' => '50000.00',
            'average_cost_after' => '50909.09',
        ]);

        return $receipt;
    }

    protected function payload(Branch $branch, Product $product, array $overrides = []): array
    {
        return [
            'branch_id' => $branch->id,
            'receipt_date' => '2026-07-25',
            'supplier_name' => 'PT Pupuk Makmur',
            'notes' => 'Penerimaan pengujian',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '5.000',
                'purchase_price' => '60000.00',
            ]],
            ...$overrides,
        ];
    }
}
