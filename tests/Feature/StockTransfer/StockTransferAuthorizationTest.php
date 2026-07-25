<?php

namespace Tests\Feature\StockTransfer;

use App\Models\StockTransfer;

class StockTransferAuthorizationTest extends StockTransferTestCase
{
    public function test_cashier_is_denied_from_all_transfer_routes(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $cashier = $this->createUser('cashier', $source);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $transfer = $this->createTransfer($source, $destination, $product, $owner);

        $this->actingAs($cashier)->get(route('stock-transfers.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('stock-transfers.create'))->assertForbidden();
        $this->actingAs($cashier)->get(route('stock-transfers.show', $transfer))->assertForbidden();
        $this->actingAs($cashier)->patch(route('stock-transfers.complete', $transfer))->assertForbidden();
    }

    public function test_admin_cannot_complete_or_reject_transfer(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $admin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $transfer = $this->createTransfer($source, $destination, $product, $admin);

        $this->actingAs($admin)->patch(route('stock-transfers.complete', $transfer))->assertForbidden();
        $this->actingAs($admin)->patch(route('stock-transfers.reject', $transfer), [
            'rejection_reason' => 'Alasan penolakan yang memenuhi validasi.',
        ])->assertForbidden();
    }

    public function test_admin_can_cancel_own_pending_request_but_not_another_users_request(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $admin = $this->createUser('admin', $source);
        $otherAdmin = $this->createUser('admin', $source);
        $product = $this->createProduct();
        $ownTransfer = $this->createTransfer($source, $destination, $product, $admin);
        $otherTransfer = $this->createTransfer($source, $destination, $product, $otherAdmin);

        $this->actingAs($admin)->patch(route('stock-transfers.cancel', $ownTransfer), [
            'cancellation_reason' => 'Kebutuhan cabang tujuan sudah terpenuhi.',
        ])->assertRedirect();
        $this->actingAs($admin)->patch(route('stock-transfers.cancel', $otherTransfer))
            ->assertForbidden();
    }

    public function test_admin_cannot_view_unrelated_transfer_or_spoof_source_branch(): void
    {
        $branchA = $this->createBranch('AAA');
        $branchB = $this->createBranch('BBB');
        $branchC = $this->createBranch('CCC');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $transfer = $this->createTransfer($branchB, $branchC, $product, $owner);

        $this->actingAs($admin)->get(route('stock-transfers.show', $transfer))->assertForbidden();
        $this->actingAs($admin)->post(route('stock-transfers.store'), $this->payload(
            $branchB,
            $branchC,
            $product,
        ))->assertSessionHasErrors('from_branch_id');
    }

    public function test_completed_transfer_cannot_be_cancelled(): void
    {
        $source = $this->createBranch('SRC');
        $destination = $this->createBranch('DST');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $transfer = $this->createTransfer($source, $destination, $product, $owner, [
            'status' => StockTransfer::STATUS_COMPLETED,
        ]);

        $this->actingAs($owner)->patch(route('stock-transfers.cancel', $transfer))
            ->assertForbidden();
    }

    public function test_sidebar_shows_transfer_menu_to_owner_and_admin_but_not_cashier(): void
    {
        $branch = $this->createBranch('MENU');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($owner)->get(route('account.index'))->assertOk()->assertSee('Mutasi Stok');
        $this->actingAs($admin)->get(route('account.index'))->assertOk()->assertSee('Mutasi Stok Cabang');
        $this->actingAs($cashier)->get(route('account.index'))->assertOk()->assertDontSee('Mutasi Stok');
    }
}
