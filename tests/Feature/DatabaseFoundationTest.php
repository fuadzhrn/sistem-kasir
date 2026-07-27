<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_tables_and_important_columns_exist(): void
    {
        $tables = [
            'roles',
            'branches',
            'users',
            'categories',
            'units',
            'products',
            'branch_stocks',
            'stock_movements',
            'stock_receipts',
            'stock_receipt_items',
            'payment_methods',
            'sales',
            'sale_items',
            'expense_categories',
            'expenses',
            'price_histories',
            'sale_voids',
            'activity_logs',
            'settings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak tersedia.");
        }

        $importantColumns = [
            'roles' => ['name', 'slug', 'description', 'is_active'],
            'branches' => ['code', 'name', 'address', 'phone', 'is_active'],
            'users' => ['role_id', 'branch_id', 'username', 'email', 'is_active', 'last_login_at'],
            'categories' => ['name', 'slug', 'description', 'is_active'],
            'units' => ['name', 'slug', 'symbol', 'is_active'],
            'products' => [
                'category_id',
                'unit_id',
                'code',
                'barcode',
                'purchase_price',
                'selling_price',
                'minimum_stock',
                'created_by',
                'updated_by',
            ],
            'branch_stocks' => ['branch_id', 'product_id', 'quantity', 'average_cost'],
            'stock_movements' => [
                'branch_id',
                'product_id',
                'movement_type',
                'reference_type',
                'reference_id',
                'quantity_before',
                'quantity_change',
                'quantity_after',
                'unit_cost',
                'created_by',
            ],
            'stock_receipts' => [
                'branch_id',
                'receipt_number',
                'receipt_date',
                'supplier_name',
                'total_cost',
                'created_by',
            ],
            'stock_receipt_items' => [
                'stock_receipt_id',
                'product_id',
                'quantity',
                'purchase_price',
                'subtotal',
            ],
            'payment_methods' => ['code', 'name', 'type', 'is_active', 'sort_order'],
            'sales' => [
                'branch_id',
                'cashier_id',
                'payment_method_id',
                'invoice_number',
                'transaction_date',
                'subtotal',
                'discount_amount',
                'total',
                'amount_paid',
                'change_amount',
                'total_cost',
                'gross_profit',
                'payment_method_name',
                'status',
                'voided_at',
            ],
            'sale_items' => [
                'sale_id',
                'product_id',
                'product_code',
                'product_name',
                'unit_name',
                'product_size',
                'quantity',
                'selling_price',
                'cost_price',
                'discount_amount',
                'subtotal',
                'profit',
            ],
            'expense_categories' => ['name', 'slug', 'description', 'is_active'],
            'expenses' => [
                'branch_id',
                'expense_category_id',
                'expense_date',
                'amount',
                'status',
                'created_by',
                'approved_by',
                'approved_at',
            ],
            'price_histories' => [
                'product_id',
                'old_purchase_price',
                'new_purchase_price',
                'old_selling_price',
                'new_selling_price',
                'changed_by',
                'changed_at',
            ],
            'sale_voids' => [
                'sale_id',
                'requested_by',
                'reviewed_by',
                'reason',
                'status',
                'reviewed_at',
                'review_notes',
                'created_at',
            ],
            'activity_logs' => [
                'user_id',
                'branch_id',
                'action',
                'module',
                'reference_type',
                'reference_id',
                'description',
                'ip_address',
                'user_agent',
                'created_at',
            ],
            'settings' => ['key', 'value', 'type', 'group', 'is_public'],
        ];

        foreach ($importantColumns as $table => $columns) {
            $this->assertTrue(
                Schema::hasColumns($table, $columns),
                "Kolom penting pada tabel {$table} belum lengkap.",
            );
        }
    }

    public function test_master_seeders_are_idempotent_and_do_not_create_users_or_secrets(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('categories', 8);
        $this->assertDatabaseCount('units', 13);
        $this->assertDatabaseCount('payment_methods', 3);
        $this->assertDatabaseCount('settings', 21);
        $this->assertDatabaseCount('users', 0);

        $this->assertSame(
            ['owner', 'admin', 'cashier'],
            Role::query()->orderBy('id')->pluck('slug')->all(),
        );
        $this->assertDatabaseHas('branches', ['code' => 'UTM', 'name' => 'Toko Utama']);
        $this->assertSame(
            ['CASH', 'TRANSFER', 'QRIS'],
            PaymentMethod::query()->orderBy('sort_order')->pluck('code')->all(),
        );

        $forbiddenFragments = ['password', 'passwd', 'secret', 'token', 'credential'];

        foreach (Setting::query()->get(['key', 'value']) as $setting) {
            $serializedSetting = strtolower($setting->key.' '.($setting->value ?? ''));

            foreach ($forbiddenFragments as $fragment) {
                $this->assertStringNotContainsString($fragment, $serializedSetting);
            }
        }
    }

    public function test_main_foreign_keys_are_available(): void
    {
        $foreignKeys = [
            'users' => [
                'role_id' => 'roles',
                'branch_id' => 'branches',
            ],
            'products' => [
                'category_id' => 'categories',
                'unit_id' => 'units',
                'created_by' => 'users',
                'updated_by' => 'users',
            ],
            'branch_stocks' => [
                'branch_id' => 'branches',
                'product_id' => 'products',
            ],
            'stock_movements' => [
                'branch_id' => 'branches',
                'product_id' => 'products',
                'created_by' => 'users',
            ],
            'stock_receipts' => [
                'branch_id' => 'branches',
                'created_by' => 'users',
            ],
            'stock_receipt_items' => [
                'stock_receipt_id' => 'stock_receipts',
                'product_id' => 'products',
            ],
            'sales' => [
                'branch_id' => 'branches',
                'cashier_id' => 'users',
                'payment_method_id' => 'payment_methods',
            ],
            'sale_items' => [
                'sale_id' => 'sales',
                'product_id' => 'products',
            ],
            'expenses' => [
                'branch_id' => 'branches',
                'expense_category_id' => 'expense_categories',
                'created_by' => 'users',
                'approved_by' => 'users',
            ],
            'price_histories' => [
                'product_id' => 'products',
                'changed_by' => 'users',
            ],
            'sale_voids' => [
                'sale_id' => 'sales',
                'requested_by' => 'users',
                'reviewed_by' => 'users',
            ],
            'activity_logs' => [
                'user_id' => 'users',
                'branch_id' => 'branches',
            ],
        ];

        foreach ($foreignKeys as $table => $expectedForeignKeys) {
            $tableForeignKeys = collect(Schema::getForeignKeys($table));

            foreach ($expectedForeignKeys as $column => $foreignTable) {
                $this->assertTrue(
                    $tableForeignKeys->contains(
                        fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                            && $foreignKey['foreign_table'] === $foreignTable,
                    ),
                    "Foreign key {$table}.{$column} menuju {$foreignTable} tidak tersedia.",
                );
            }
        }
    }

    public function test_core_model_relationships_are_connected(): void
    {
        [$role, $branch, $user, $category, $unit, $product] = $this->createReferenceGraph();

        $stock = BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '12.500',
            'average_cost' => '42500.25',
        ]);
        $paymentMethod = PaymentMethod::factory()->create();
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_method_name' => $paymentMethod->name,
        ]);
        $saleItem = $this->createSaleItem($sale, $product);
        $expenseCategory = ExpenseCategory::query()->create([
            'name' => 'Operasional',
            'slug' => 'operasional',
            'description' => null,
            'is_active' => true,
        ]);
        $expense = Expense::query()->create([
            'branch_id' => $branch->id,
            'expense_category_id' => $expenseCategory->id,
            'expense_date' => now()->toDateString(),
            'amount' => '15000.00',
            'description' => 'Pengujian relasi pengeluaran.',
            'status' => Expense::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($user->role->is($role));
        $this->assertTrue($user->branch->is($branch));
        $this->assertTrue($role->users->contains($user));
        $this->assertTrue($branch->users->contains($user));
        $this->assertTrue($product->category->is($category));
        $this->assertTrue($product->unit->is($unit));
        $this->assertTrue($stock->branch->is($branch));
        $this->assertTrue($stock->product->is($product));
        $this->assertTrue($sale->branch->is($branch));
        $this->assertTrue($sale->cashier->is($user));
        $this->assertTrue($sale->paymentMethod->is($paymentMethod));
        $this->assertTrue($sale->items->contains($saleItem));
        $this->assertTrue($saleItem->sale->is($sale));
        $this->assertTrue($expense->branch->is($branch));
        $this->assertTrue($expense->expenseCategory->is($expenseCategory));
        $this->assertTrue($expense->creator->is($user));
    }

    public function test_money_quantity_casts_and_sale_item_snapshots_are_preserved(): void
    {
        [, $branch, $user, , , $product] = $this->createReferenceGraph();
        $paymentMethod = PaymentMethod::factory()->create();
        $stock = BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '12.500',
            'average_cost' => '42500.25',
        ]);
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'subtotal' => '127501.25',
            'total' => '127501.25',
            'amount_paid' => '130000.00',
            'change_amount' => '2498.75',
        ]);
        $saleItem = $this->createSaleItem($sale, $product);

        $product->update([
            'code' => 'PRD-BARU',
            'name' => 'Nama Produk Baru',
            'size' => '2 liter',
            'selling_price' => '99000.00',
        ]);

        $this->assertSame('12.500', $stock->fresh()->quantity);
        $this->assertSame('42500.25', $stock->fresh()->average_cost);
        $this->assertSame('127501.25', $sale->fresh()->total);
        $this->assertSame('2498.75', $sale->fresh()->change_amount);
        $this->assertSame('1.250', $saleItem->fresh()->quantity);
        $this->assertSame('75000.50', $saleItem->fresh()->selling_price);
        $this->assertSame('PRD-SNAPSHOT', $saleItem->fresh()->product_code);
        $this->assertSame('Produk Snapshot', $saleItem->fresh()->product_name);
        $this->assertSame('1 liter', $saleItem->fresh()->product_size);
    }

    public function test_product_code_must_be_unique(): void
    {
        [, , , $category, $unit] = $this->createReferenceGraph();

        Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'code' => 'PRD-UNIK',
            'barcode' => null,
        ]);

        $this->expectException(QueryException::class);

        Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'code' => 'PRD-UNIK',
            'barcode' => null,
        ]);
    }

    public function test_branch_and_product_stock_pair_must_be_unique(): void
    {
        [, $branch, , , , $product] = $this->createReferenceGraph();

        BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '1.000',
            'average_cost' => '1000.00',
        ]);

        $this->expectException(QueryException::class);

        BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '2.000',
            'average_cost' => '1000.00',
        ]);
    }

    public function test_invoice_number_must_be_unique(): void
    {
        [, $branch, $user] = $this->createReferenceGraph();
        $paymentMethod = PaymentMethod::factory()->create();
        $attributes = [
            'branch_id' => $branch->id,
            'cashier_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'invoice_number' => 'INV-UNIK-001',
        ];

        Sale::factory()->create($attributes);

        $this->expectException(QueryException::class);

        Sale::factory()->create($attributes);
    }

    public function test_sale_item_keeps_snapshot_and_nulls_product_reference_after_product_deletion(): void
    {
        [, $branch, $user, , , $product] = $this->createReferenceGraph();
        $paymentMethod = PaymentMethod::factory()->create();
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
        $saleItem = $this->createSaleItem($sale, $product);

        $product->delete();
        $saleItem->refresh();

        $this->assertNull($saleItem->product_id);
        $this->assertSame('PRD-SNAPSHOT', $saleItem->product_code);
        $this->assertSame('Produk Snapshot', $saleItem->product_name);
    }

    public function test_activity_log_cannot_be_changed_after_creation(): void
    {
        [, $branch, $user] = $this->createReferenceGraph();
        $activityLog = ActivityLog::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'action' => 'created',
            'module' => 'testing',
            'description' => 'Catatan awal.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $activityLog->description = 'Catatan yang tidak boleh tersimpan.';

        $this->assertFalse($activityLog->save());
        $this->assertSame('Catatan awal.', $activityLog->fresh()->description);
    }

    /**
     * @return array{Role, Branch, User, Category, Unit, Product}
     */
    private function createReferenceGraph(): array
    {
        $role = Role::factory()->create();
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'code' => 'PRD-AWAL',
            'barcode' => null,
        ]);

        return [$role, $branch, $user, $category, $unit, $product];
    }

    private function createSaleItem(Sale $sale, Product $product): SaleItem
    {
        return SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_code' => 'PRD-SNAPSHOT',
            'product_name' => 'Produk Snapshot',
            'unit_name' => 'Botol',
            'product_size' => '1 liter',
            'quantity' => '1.250',
            'selling_price' => '75000.50',
            'cost_price' => '50000.25',
            'discount_amount' => '0.00',
            'subtotal' => '93750.63',
            'profit' => '31250.31',
        ]);
    }
}
