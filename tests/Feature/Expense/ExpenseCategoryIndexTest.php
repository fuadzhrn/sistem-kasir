<?php

namespace Tests\Feature\Expense;

class ExpenseCategoryIndexTest extends ExpenseTestCase
{
    public function test_owner_and_admin_can_list_and_filter_global_categories(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $active = $this->createCategory(['name' => 'Listrik Toko']);
        $this->createCategory(['name' => 'Perawatan Kendaraan', 'is_active' => false]);

        $this->actingAs($owner)->get(route('expense-categories.index', ['search' => 'Listrik']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee('Perawatan Kendaraan');
        $this->actingAs($admin)->get(route('expense-categories.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Perawatan Kendaraan');
    }
}
