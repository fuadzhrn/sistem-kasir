<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;

class ExpenseCreateTest extends ExpenseTestCase
{
    public function test_owner_creates_pending_expense_and_rupiah_input_is_normalized(): void
    {
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory();

        $response = $this->actingAs($owner)->post(route('expenses.store'), $this->payload($branch, $category, [
            'status' => 'approved',
            'created_by' => 999999,
            'approved_by' => $owner->id,
            'approved_at' => now()->toDateTimeString(),
            'rejected_by' => $owner->id,
            'rejected_at' => now()->toDateTimeString(),
            'rejection_reason' => 'Nilai manipulasi',
            'proof_file' => 'expense-proofs/manipulasi.php',
        ]));

        $expense = Expense::query()->firstOrFail();
        $response->assertRedirect(route('expenses.show', $expense));
        $this->assertSame('150000.00', $expense->amount);
        $this->assertSame('pending', $expense->status);
        $this->assertSame($owner->id, $expense->created_by);
        $this->assertNull($expense->approved_by);
        $this->assertNull($expense->approved_at);
        $this->assertNull($expense->rejected_by);
        $this->assertNull($expense->rejected_at);
        $this->assertNull($expense->rejection_reason);
        $this->assertNull($expense->proof_file);
    }

    public function test_admin_is_forced_to_own_branch_and_invalid_values_are_rejected(): void
    {
        $branchA = $this->createBranch('ECA');
        $branchB = $this->createBranch('ECB');
        $admin = $this->createUser('admin', $branchA);
        $category = $this->createCategory();

        $payload = $this->payload($branchB, $category, [
            'amount' => '0',
            'expense_date' => now()->addDay()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('expenses.store'), $payload)
            ->assertSessionHasErrors(['branch_id', 'amount', 'expense_date']);
        $this->assertDatabaseCount('expenses', 0);

        unset($payload['branch_id']);
        $payload['amount'] = '25.000';
        $payload['expense_date'] = now()->toDateString();
        $this->actingAs($admin)->post(route('expenses.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('expenses', ['branch_id' => $branchA->id, 'amount' => '25000.00']);
    }
}
