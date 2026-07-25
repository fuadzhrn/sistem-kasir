<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'expense_date' => now()->toDateString(),
            'amount' => '25000.00',
            'description' => fake()->sentence(),
            'proof_file' => null,
            'status' => Expense::STATUS_PENDING,
            'created_by' => User::factory(),
            'updated_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }
}
