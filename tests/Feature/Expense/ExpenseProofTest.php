<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExpenseProofTest extends ExpenseTestCase
{
    public function test_valid_proof_is_stored_with_random_name_and_can_be_replaced(): void
    {
        Storage::fake('public');
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $payload = $this->payload($branch, $category, [
            'proof' => UploadedFile::fake()->image('nota-asli.jpg'),
        ]);

        $this->actingAs($owner)->post(route('expenses.store'), $payload)->assertRedirect();
        $expense = Expense::query()->firstOrFail();
        $oldProof = $expense->proof_file;
        $this->assertStringStartsWith('expense-proofs/', $oldProof);
        $this->assertStringNotContainsString('nota-asli', $oldProof);
        Storage::disk('public')->assertExists($oldProof);

        $update = $this->payload($branch, $category, [
            'proof' => UploadedFile::fake()->image('pengganti.webp'),
        ]);
        unset($update['branch_id']);
        $this->actingAs($owner)->put(route('expenses.update', $expense), $update)->assertRedirect();
        $expense->refresh();
        Storage::disk('public')->assertMissing($oldProof);
        Storage::disk('public')->assertExists($expense->proof_file);
    }

    public function test_invalid_proof_is_rejected_and_pending_proof_can_be_removed(): void
    {
        Storage::fake('public');
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        $category = $this->createCategory();

        $this->actingAs($owner)->post(route('expenses.store'), $this->payload($branch, $category, [
            'proof' => UploadedFile::fake()->create('script.php', 4, 'application/x-php'),
        ]))->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('expenses', 0);

        Storage::disk('public')->put('expense-proofs/proof.png', 'image');
        $expense = $this->createExpense($branch, $owner, $category, [
            'proof_file' => 'expense-proofs/proof.png',
        ]);
        $this->actingAs($owner)->delete(route('expenses.proof.destroy', $expense))->assertRedirect();
        $this->assertNull($expense->fresh()->proof_file);
        Storage::disk('public')->assertMissing('expense-proofs/proof.png');
    }

    public function test_final_expense_proof_is_immutable(): void
    {
        Storage::fake('public');
        $branch = $this->createBranch();
        $owner = $this->createUser('owner');
        Storage::disk('public')->put('expense-proofs/final.png', 'image');
        $expense = $this->createExpense($branch, $owner, null, [
            'proof_file' => 'expense-proofs/final.png',
            'status' => 'approved',
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($owner)->delete(route('expenses.proof.destroy', $expense))->assertForbidden();
        Storage::disk('public')->assertExists('expense-proofs/final.png');
    }
}
