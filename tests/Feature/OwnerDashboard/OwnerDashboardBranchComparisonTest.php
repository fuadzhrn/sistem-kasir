<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Expense;
use App\Models\Sale;

class OwnerDashboardBranchComparisonTest extends OwnerDashboardTestCase
{
    public function test_branch_comparison_is_sorted_and_uses_active_sales_and_approved_expenses(): void
    {
        $owner = $this->createUser('owner');
        $branchA = $this->createBranch('BCA');
        $branchB = $this->createBranch('BCB', ['is_active' => false]);
        $this->createSale($branchA, $owner);
        $this->createSale($branchB, $owner, [
            'total' => '300000.00',
            'subtotal' => '300000.00',
            'total_cost' => '100000.00',
        ]);
        $this->createSale($branchB, $owner, [
            'status' => Sale::STATUS_VOIDED,
            'total' => '999000.00',
        ]);
        $this->createExpense($branchB, $owner, Expense::STATUS_APPROVED, [
            'amount' => '50000.00',
        ]);

        $this->getDashboardData($owner)
            ->assertOk()
            ->assertJsonPath('data.charts.branch_comparison.labels.0', $branchB->name)
            ->assertJsonPath('data.charts.branch_comparison.net_sales', [300000, 180000])
            ->assertJsonPath('data.charts.branch_comparison.net_profit', [150000, 60000]);

        $this->getDashboardData($owner, ['branch_id' => $branchA->id])
            ->assertJsonPath('data.charts.branch_comparison.labels', [$branchA->name])
            ->assertJsonPath('data.charts.branch_comparison.net_sales', [180000]);
    }

    public function test_more_than_twelve_branches_are_grouped_without_losing_totals(): void
    {
        $owner = $this->createUser('owner');

        for ($index = 1; $index <= 13; $index++) {
            $branch = $this->createBranch('G'.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
            $this->createSale($branch, $owner, [
                'total' => (string) ($index * 10000).'.00',
                'subtotal' => (string) ($index * 10000).'.00',
                'total_cost' => '0.00',
            ]);
        }

        $response = $this->getDashboardData($owner)->assertOk();
        $this->assertCount(12, $response->json('data.charts.branch_comparison.labels'));
        $this->assertContains(
            'Cabang Lainnya',
            $response->json('data.charts.branch_comparison.labels'),
        );
        $response->assertJsonPath('data.charts.branch_comparison.grouped_others', true);
        $this->assertSame(
            910000,
            array_sum($response->json('data.charts.branch_comparison.net_sales')),
        );
    }
}
