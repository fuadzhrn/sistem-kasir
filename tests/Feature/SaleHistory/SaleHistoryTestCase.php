<?php

namespace Tests\Feature\SaleHistory;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SaleHistoryTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createBranch(string $code, array $attributes = []): Branch
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
            ['name' => ucfirst($roleSlug), 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch?->id,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createPaymentMethod(string $name = 'Tunai'): PaymentMethod
    {
        return PaymentMethod::factory()->create([
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)).fake()->unique()->numerify('###'),
        ]);
    }

    protected function createSale(
        Branch $branch,
        User $cashier,
        string $invoiceNumber,
        array $attributes = [],
    ): Sale {
        $paymentMethod = $attributes['payment_method'] ?? $this->createPaymentMethod();
        unset($attributes['payment_method']);

        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_method_name' => $paymentMethod->name,
            'invoice_number' => $invoiceNumber,
            'transaction_date' => '2026-07-24 14:35:00',
            'subtotal' => '175000.00',
            'discount_amount' => '5000.00',
            'total' => '170000.00',
            'amount_paid' => '200000.00',
            'change_amount' => '30000.00',
            'total_cost' => '98765.43',
            'gross_profit' => '71234.57',
            ...$attributes,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => null,
            'product_code' => 'SNAP-001',
            'product_name' => 'Pupuk Snapshot',
            'unit_name' => 'Sak',
            'product_size' => '50 kg',
            'quantity' => '2.500',
            'selling_price' => '70000.00',
            'cost_price' => '39506.17',
            'discount_amount' => '5000.00',
            'subtotal' => '170000.00',
            'profit' => '71234.57',
        ]);

        return $sale;
    }
}
