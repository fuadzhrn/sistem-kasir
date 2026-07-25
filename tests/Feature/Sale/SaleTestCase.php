<?php

namespace Tests\Feature\Sale;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SaleTestCase extends TestCase
{
    use RefreshDatabase;

    private int $tokenSequence = 0;

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
        $category = $attributes['category_id'] ?? Category::factory()->create(['is_active' => true])->id;
        $unit = $attributes['unit_id'] ?? Unit::factory()->create([
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'is_active' => true,
        ])->id;

        return Product::factory()->create([
            'category_id' => $category,
            'unit_id' => $unit,
            'code' => $attributes['code'] ?? 'PRD-'.fake()->unique()->numerify('#####'),
            'name' => $attributes['name'] ?? 'Produk Uji',
            'size' => $attributes['size'] ?? '1 kg',
            'purchase_price' => '99999.00',
            'selling_price' => '20000.00',
            'minimum_stock' => '1.000',
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

    protected function setCashierDiscount(string $value): Setting
    {
        return Setting::query()->updateOrCreate(
            ['key' => 'maximum_cashier_discount'],
            [
                'value' => $value,
                'type' => 'decimal',
                'group' => 'sales',
                'is_public' => false,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(
        User $user,
        Branch $branch,
        Product $product,
        PaymentMethod $paymentMethod,
        array $overrides = [],
    ): array {
        $payload = [
            'checkout_token' => $this->nextToken(),
            ...($user->isOwner() ? ['branch_id' => $branch->id] : []),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '2.000',
            ]],
            'discount_amount' => '0.00',
            'payment_method_id' => $paymentMethod->id,
            'amount_received' => '50000.00',
            'payment_action' => 'no_print',
            'expected_subtotal' => '40000.00',
            'expected_total' => '40000.00',
            'notes' => null,
        ];

        return array_replace_recursive($payload, $overrides);
    }

    protected function nextToken(): string
    {
        $this->tokenSequence++;

        return 'checkout-test-'.str_pad((string) $this->tokenSequence, 24, '0', STR_PAD_LEFT);
    }
}
