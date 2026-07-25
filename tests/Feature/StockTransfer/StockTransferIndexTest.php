<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockTransfer;

class StockTransferIndexTest extends StockTransferTestCase
{
    public function test_owner_sees_all_transfers_and_can_filter_status(): void
    {
        $source = $this->createBranch('AAA');
        $destination = $this->createBranch('BBB');
        $third = $this->createBranch('CCC');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $pending = $this->createTransfer($source, $destination, $product, $admin);
        $completed = $this->createTransfer($destination, $third, $product, $owner, [
            'status' => StockTransfer::STATUS_COMPLETED,
        ]);

        $this->actingAs($owner)->get(route('stock-transfers.index'))
            ->assertOk()
            ->assertSee($pending->transfer_number)
            ->assertSee($completed->transfer_number);

        $this->actingAs($owner)->get(route('stock-transfers.index', [
            'status' => StockTransfer::STATUS_COMPLETED,
        ]))->assertOk()
            ->assertDontSee($pending->transfer_number)
            ->assertSee($completed->transfer_number);
    }

    public function test_admin_only_sees_transfers_involving_own_branch(): void
    {
        $branchA = $this->createBranch('A01');
        $branchB = $this->createBranch('B01');
        $branchC = $this->createBranch('C01');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $outgoing = $this->createTransfer($branchA, $branchB, $product, $admin);
        $incoming = $this->createTransfer($branchC, $branchA, $product, $owner);
        $unrelated = $this->createTransfer($branchB, $branchC, $product, $owner);

        $this->actingAs($admin)->get(route('stock-transfers.index'))
            ->assertOk()
            ->assertSee($outgoing->transfer_number)
            ->assertSee($incoming->transfer_number)
            ->assertDontSee($unrelated->transfer_number);
    }

    public function test_list_is_paginated_and_searches_number_or_product(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['code' => 'UNIK-TRF', 'name' => 'Produk Mutasi Unik']);

        foreach (range(1, 21) as $index) {
            $this->createTransfer($source, $destination, $product, $owner, [
                'transfer_number' => 'TRF-SRC-DST-20260725-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($owner)->get(route('stock-transfers.index', ['search' => 'UNIK-TRF']))
            ->assertOk()
            ->assertSee('Menampilkan 1-20 dari 21');
    }
}
