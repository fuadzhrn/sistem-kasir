<?php

namespace Tests\Feature\Expense;

class ExpenseIndexTest extends ExpenseTestCase
{
    public function test_owner_sees_all_branches_while_admin_sees_only_own_branch(): void
    {
        $branchA = $this->createBranch('EXA');
        $branchB = $this->createBranch('EXB');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $category = $this->createCategory();
        $own = $this->createExpense($branchA, $adminA, $category, ['description' => 'Biaya Cabang A']);
        $other = $this->createExpense($branchB, $owner, $category, ['description' => 'Biaya Cabang B']);

        $this->actingAs($owner)->get(route('expenses.index'))
            ->assertOk()->assertSee($own->description)->assertSee($other->description);
        $this->actingAs($adminA)->get(route('expenses.index', ['branch_id' => $branchB->id]))
            ->assertOk()->assertSee($own->description)->assertDontSee($other->description);
    }

    public function test_index_filters_status_category_creator_date_and_search(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory(['name' => 'Listrik']);
        $match = $this->createExpense($branch, $owner, $category, ['description' => 'Token listrik gudang']);
        $this->createExpense($branch, $owner, $category, [
            'description' => 'Biaya lama',
            'status' => 'rejected',
            'rejection_reason' => 'Tidak sesuai',
            'rejected_by' => $owner->id,
            'rejected_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('expenses.index', [
            'status' => 'pending',
            'expense_category_id' => $category->id,
            'created_by' => $owner->id,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'search' => 'Token',
        ]))->assertOk()->assertSee($match->description)->assertDontSee('Biaya lama');
    }
}
