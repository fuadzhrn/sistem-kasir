<?php

namespace Tests\Feature\Expense;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Gate;

class ExpenseCategoryAuthorizationTest extends ExpenseTestCase
{
    public function test_guest_cashier_and_inactive_user_cannot_manage_categories(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $inactive = $this->createUser('admin', $branch, ['is_active' => false]);
        $category = $this->createCategory();

        $this->get(route('expense-categories.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('expense-categories.index'))->assertForbidden();
        $this->assertTrue(Gate::forUser($inactive)->denies('update', $category));
        $this->assertTrue(Gate::forUser($inactive)->denies('viewAny', ExpenseCategory::class));
    }
}
