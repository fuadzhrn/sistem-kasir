<?php

namespace Tests\Feature\SaleHistory;

use App\Models\Sale;

class SaleHistoryFilterTest extends SaleHistoryTestCase
{
    public function test_search_supports_exact_partial_old_and_new_numbers(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $this->createSale($branch, $cashier, 'INV-AAA-20260723-0009');
        $this->createSale($branch, $cashier, 'AAA-20260724-0001');

        $this->actingAs($owner)->get(route('sales.index', ['search' => 'INV-AAA-20260723-0009']))
            ->assertSee('INV-AAA-20260723-0009')
            ->assertDontSee('AAA-20260724-0001');
        $this->actingAs($owner)->get(route('sales.index', ['search' => '20260724']))
            ->assertSee('AAA-20260724-0001')
            ->assertDontSee('INV-AAA-20260723-0009');
    }

    public function test_date_status_cashier_branch_and_payment_filters_work(): void
    {
        $branchA = $this->createBranch('AAA');
        $branchB = $this->createBranch('BBB');
        $owner = $this->createUser('owner');
        $cashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $cash = $this->createPaymentMethod('Tunai');
        $transfer = $this->createPaymentMethod('Transfer');
        $match = $this->createSale($branchA, $cashierA, 'AAA-20260724-0001', [
            'payment_method' => $transfer,
            'status' => Sale::STATUS_VOID_REQUESTED,
            'transaction_date' => '2026-07-24 23:59:59',
        ]);
        $this->createSale($branchB, $cashierB, 'BBB-20260725-0001', [
            'payment_method' => $cash,
            'status' => Sale::STATUS_COMPLETED,
            'transaction_date' => '2026-07-25 00:00:00',
        ]);

        $this->actingAs($owner)->get(route('sales.index', [
            'branch_id' => $branchA->id,
            'cashier_id' => $cashierA->id,
            'payment_method_id' => $transfer->id,
            'status' => Sale::STATUS_VOID_REQUESTED,
            'date_from' => '2026-07-24',
            'date_to' => '2026-07-24',
        ]))->assertOk()
            ->assertSee($match->invoice_number)
            ->assertDontSee('BBB-20260725-0001');
    }

    public function test_invalid_range_and_per_page_are_rejected_and_query_is_preserved(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->get(route('sales.index', [
            'date_from' => '2026-07-25',
            'date_to' => '2026-07-24',
            'per_page' => 1000,
        ]))->assertSessionHasErrors(['date_to', 'per_page']);

        $branch = $this->createBranch('AAA');
        $cashier = $this->createUser('cashier', $branch);
        for ($index = 1; $index <= 16; $index++) {
            $this->createSale($branch, $cashier, sprintf('AAA-20260724-%04d', $index));
        }

        $this->actingAs($owner)->get(route('sales.index', [
            'search' => 'AAA-',
            'per_page' => 15,
        ]))->assertOk()
            ->assertSee('search=AAA-', false)
            ->assertSee('per_page=15', false);
    }
}
