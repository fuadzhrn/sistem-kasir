<?php

namespace Tests\Feature\Expense;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ExpenseTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createBranch(string $code = 'EXP', array $attributes = []): Branch
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

    protected function createCategory(array $attributes = []): ExpenseCategory
    {
        $creator = $attributes['creator'] ?? $this->createUser('owner');
        unset($attributes['creator']);

        return ExpenseCategory::factory()->create([
            'name' => 'Operasional Uji',
            'slug' => 'operasional-uji-'.fake()->unique()->numberBetween(100, 999),
            'is_active' => true,
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    protected function createExpense(
        Branch $branch,
        User $creator,
        ?ExpenseCategory $category = null,
        array $attributes = [],
    ): Expense {
        return Expense::factory()->create([
            'branch_id' => $branch->id,
            'expense_category_id' => ($category ?? $this->createCategory())->id,
            'expense_date' => now()->toDateString(),
            'amount' => '125000.00',
            'description' => 'Biaya operasional untuk pengujian modul',
            'status' => Expense::STATUS_PENDING,
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Branch $branch, ExpenseCategory $category, array $overrides = []): array
    {
        return [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => '150.000',
            'description' => 'Pembelian perlengkapan operasional toko',
            ...$overrides,
        ];
    }
}
