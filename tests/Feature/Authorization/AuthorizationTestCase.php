<?php

namespace Tests\Feature\Authorization;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AuthorizationTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createBranch(string $code, ?string $name = null): Branch
    {
        return Branch::factory()->create([
            'code' => $code,
            'name' => $name ?? "Cabang {$code}",
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createUser(string $roleSlug, ?Branch $branch = null, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => match ($roleSlug) {
                    'owner' => 'Owner',
                    'admin' => 'Admin/Kepala Cabang',
                    'cashier' => 'Kasir/Pegawai',
                    default => ucfirst($roleSlug),
                },
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

    protected function createSale(Branch $branch, User $cashier, string $invoice): Sale
    {
        return Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'payment_method_id' => PaymentMethod::factory(),
            'invoice_number' => $invoice,
        ]);
    }

    protected function createExpense(Branch $branch, User $creator, string $description): Expense
    {
        return Expense::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'description' => $description,
        ]);
    }
}
