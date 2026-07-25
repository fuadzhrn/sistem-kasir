<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class OwnerDashboardTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-07-25 14:35:00', 'Asia/Jakarta'),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    protected function createBranch(string $code = 'DBA', array $attributes = []): Branch
    {
        return Branch::factory()->create([
            'code' => $code,
            'name' => 'Cabang '.$code,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createUser(
        string $roleSlug,
        ?Branch $branch = null,
        array $attributes = [],
    ): User {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst($roleSlug), 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch?->id,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createProduct(
        string $code = 'DASH-001',
        array $attributes = [],
    ): Product {
        $unit = Unit::factory()->create([
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'is_active' => true,
        ]);

        return Product::factory()->create([
            'category_id' => Category::factory()->create(['is_active' => true]),
            'unit_id' => $unit->id,
            'code' => $code,
            'name' => 'Produk '.$code,
            'minimum_stock' => '5.000',
            'purchase_price' => '50000.00',
            'selling_price' => '90000.00',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /**
     * @return array{sale: Sale, item: SaleItem, payment: PaymentMethod}
     */
    protected function createSale(
        Branch $branch,
        User $cashier,
        array $attributes = [],
        ?Product $product = null,
        array $itemAttributes = [],
    ): array {
        $paymentName = $attributes['payment_method_name'] ?? 'Tunai';
        $payment = PaymentMethod::factory()->create([
            'code' => fake()->unique()->bothify('PM-####'),
            'name' => $paymentName,
            'type' => $paymentName === 'Tunai' ? 'cash' : 'non_cash',
            'is_active' => true,
        ]);
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'payment_method_id' => $payment->id,
            'invoice_number' => fake()->unique()->bothify('INV-########'),
            'transaction_date' => '2026-07-10 10:00:00',
            'subtotal' => '200000.00',
            'discount_amount' => '20000.00',
            'total' => '180000.00',
            'amount_paid' => '200000.00',
            'change_amount' => '20000.00',
            'total_cost' => '120000.00',
            'gross_profit' => '60000.00',
            'payment_method_name' => $paymentName,
            'status' => Sale::STATUS_COMPLETED,
            ...$attributes,
        ]);
        $product ??= $this->createProduct(fake()->unique()->bothify('PR-####'));
        $item = SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit_name' => $product->unit->name,
            'product_size' => $product->size,
            'quantity' => '2.000',
            'selling_price' => '100000.00',
            'cost_price' => '60000.00',
            'discount_amount' => '20000.00',
            'subtotal' => '180000.00',
            'profit' => '60000.00',
            ...$itemAttributes,
        ]);

        return compact('sale', 'item', 'payment');
    }

    protected function createExpense(
        Branch $branch,
        User $creator,
        string $status = Expense::STATUS_APPROVED,
        array $attributes = [],
    ): Expense {
        $category = ExpenseCategory::factory()->create([
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ]);

        return Expense::factory()->create([
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'expense_date' => '2026-07-11',
            'amount' => '25000.00',
            'description' => 'Biaya operasional pengujian dashboard.',
            'status' => $status,
            'created_by' => $creator->id,
            'approved_by' => $status === Expense::STATUS_APPROVED ? $creator->id : null,
            'approved_at' => $status === Expense::STATUS_APPROVED ? now() : null,
            ...$attributes,
        ]);
    }

    protected function createStock(
        Branch $branch,
        Product $product,
        string $quantity = '2.000',
    ): BranchStock {
        return BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'average_cost' => '50000.00',
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function getDashboardData(User $owner, array $parameters = [])
    {
        return $this->actingAs($owner)
            ->getJson(route('dashboard.owner.data', [
                'period' => 'this_month',
                ...$parameters,
            ]));
    }
}
