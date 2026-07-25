<?php

namespace Tests\Feature\StockReceipt;

class StockReceiptIndexTest extends StockReceiptTestCase
{
    public function test_owner_can_view_filter_and_search_all_branch_receipts(): void
    {
        $branchA = $this->createBranch('A01');
        $branchB = $this->createBranch('B01');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['name' => 'Pupuk Urea']);
        $receiptA = $this->createReceipt($branchA, $owner, $product, [
            'receipt_number' => 'BM-A01-20260724-0001',
            'receipt_date' => '2026-07-24',
            'supplier_name' => 'Supplier Anggrek',
        ]);
        $receiptB = $this->createReceipt($branchB, $owner, $product, [
            'receipt_number' => 'BM-B01-20260725-0001',
            'supplier_name' => 'Supplier Bumi',
        ]);

        $this->actingAs($owner)->get(route('stock-receipts.index'))
            ->assertOk()
            ->assertSee($receiptA->receipt_number)
            ->assertSee($receiptB->receipt_number);

        $this->actingAs($owner)->get(route('stock-receipts.index', ['branch_id' => $branchA->id]))
            ->assertOk()
            ->assertSee($receiptA->receipt_number)
            ->assertDontSee($receiptB->receipt_number);

        $this->actingAs($owner)->get(route('stock-receipts.index', ['search' => 'Supplier Bumi']))
            ->assertOk()
            ->assertSee($receiptB->receipt_number)
            ->assertDontSee($receiptA->receipt_number);

        $this->actingAs($owner)->get(route('stock-receipts.index', [
            'date_from' => '2026-07-24',
            'date_to' => '2026-07-24',
        ]))->assertOk()->assertSee($receiptA->receipt_number)->assertDontSee($receiptB->receipt_number);
    }

    public function test_admin_only_sees_own_branch_and_can_view_document_purchase_prices_without_cost_snapshots(): void
    {
        $branchA = $this->createBranch('A02');
        $branchB = $this->createBranch('B02');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['name' => 'Herbisida Aman']);
        $ownReceipt = $this->createReceipt($branchA, $admin, $product, [
            'receipt_number' => 'BM-A02-20260725-0001',
        ]);
        $otherReceipt = $this->createReceipt($branchB, $owner, $product, [
            'receipt_number' => 'BM-B02-20260725-0001',
        ]);

        $this->actingAs($admin)->get(route('stock-receipts.index'))
            ->assertOk()
            ->assertSee($ownReceipt->receipt_number)
            ->assertDontSee($otherReceipt->receipt_number)
            ->assertDontSee('name="branch_id"', false);

        $this->actingAs($admin)->get(route('stock-receipts.show', $ownReceipt))
            ->assertOk()
            ->assertSee('Rp60.000')
            ->assertDontSee('average_cost_before')
            ->assertDontSee('average_cost_after')
            ->assertDontSee('Harga modal rata-rata');

        $this->actingAs($admin)->get(route('stock-receipts.show', $otherReceipt))->assertForbidden();
    }

    public function test_receipt_index_is_paginated(): void
    {
        $branch = $this->createBranch('PG1');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();

        for ($sequence = 1; $sequence <= 21; $sequence++) {
            $this->createReceipt($branch, $owner, $product, [
                'receipt_number' => 'BM-PG1-20260725-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($owner)->get(route('stock-receipts.index'))
            ->assertOk()
            ->assertSee('Menampilkan 1-20 dari 21')
            ->assertSee('BM-PG1-20260725-0021')
            ->assertDontSee('BM-PG1-20260725-0001');
    }
}
