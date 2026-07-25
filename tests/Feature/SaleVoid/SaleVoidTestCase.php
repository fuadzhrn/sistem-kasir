<?php

namespace Tests\Feature\SaleVoid;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SaleVoidTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createBranch(string $code = 'VDA'): Branch
    {
        return Branch::factory()->create([
            'code' => $code,
            'name' => 'Cabang '.$code,
            'is_active' => true,
        ]);
    }

    protected function createUser(string $roleSlug, ?Branch $branch = null, array $attributes = []): User
    {
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

    protected function createProduct(?string $code = null): Product
    {
        $code ??= 'VOID-'.fake()->unique()->numerify('#####');

        return Product::factory()->create([
            'category_id' => Category::factory()->create(['is_active' => true]),
            'unit_id' => Unit::factory()->create([
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'is_active' => true,
            ]),
            'code' => $code,
            'name' => 'Produk '.$code,
            'purchase_price' => '50000.00',
            'selling_price' => '90000.00',
            'is_active' => true,
        ]);
    }

    protected function createPaymentMethod(string $type = 'cash', string $name = 'Tunai'): PaymentMethod
    {
        return PaymentMethod::factory()->create([
            'code' => strtoupper(substr($name, 0, 3)).fake()->unique()->numerify('###'),
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{sale: Sale, product: Product, stock: BranchStock, payment: PaymentMethod}
     */
    protected function createVoidableSale(
        Branch $branch,
        User $cashier,
        string $paymentType = 'cash',
        array $saleAttributes = [],
        array $itemAttributes = [],
        array $stockAttributes = [],
    ): array {
        $payment = $this->createPaymentMethod(
            $paymentType,
            $paymentType === 'cash' ? 'Tunai' : ($paymentType === 'non_cash' ? 'QRIS' : 'Pembayaran Lain'),
        );
        $product = $this->createProduct();
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'payment_method_id' => $payment->id,
            'payment_method_name' => $payment->name,
            'invoice_number' => $branch->code.'-20260725-0001',
            'transaction_date' => '2026-07-25 10:00:00',
            'subtotal' => '180000.00',
            'discount_amount' => '0.00',
            'total' => '180000.00',
            'amount_paid' => '200000.00',
            'change_amount' => '20000.00',
            'total_cost' => '100000.00',
            'gross_profit' => '80000.00',
            'status' => Sale::STATUS_COMPLETED,
            'voided_at' => null,
            ...$saleAttributes,
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'unit_name' => 'Kilogram',
            'product_size' => null,
            'quantity' => '2.000',
            'selling_price' => '90000.00',
            'cost_price' => '50000.00',
            'discount_amount' => '0.00',
            'subtotal' => '180000.00',
            'profit' => '80000.00',
            ...$itemAttributes,
        ]);
        $stock = BranchStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => '10.000',
            'average_cost' => '60000.00',
            ...$stockAttributes,
        ]);

        return compact('sale', 'product', 'stock', 'payment');
    }

    /**
     * @return array<string, mixed>
     */
    protected function voidPayload(bool $refundConfirmed = false, array $overrides = []): array
    {
        return [
            'reason' => 'Pelanggan membatalkan seluruh transaksi karena salah memilih produk.',
            'confirmation' => '1',
            ...($refundConfirmed ? ['refund_confirmed' => '1'] : []),
            ...$overrides,
        ];
    }
}
