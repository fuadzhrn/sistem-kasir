<?php

namespace Tests\Feature\StockReceipt;

use App\Models\StockReceipt;
use Illuminate\Support\Facades\Gate;

class StockReceiptAuthorizationTest extends StockReceiptTestCase
{
    public function test_guest_cashier_and_inactive_users_cannot_access_module(): void
    {
        $branch = $this->createBranch('AUTH');
        $cashier = $this->createUser('cashier', $branch);
        $inactiveOwner = $this->createUser('owner', null, ['is_active' => false]);

        $this->get(route('stock-receipts.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('stock-receipts.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('stock-receipts.create'))->assertForbidden();
        $this->assertTrue(Gate::forUser($inactiveOwner)->denies('viewAny', StockReceipt::class));
    }

    public function test_admin_cannot_spoof_or_open_another_branch(): void
    {
        $branchA = $this->createBranch('AA1');
        $branchB = $this->createBranch('AB1');
        $admin = $this->createUser('admin', $branchA);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $otherReceipt = $this->createReceipt($branchB, $owner, $product);
        $payload = $this->payload($branchB, $product);

        $this->actingAs($admin)->post(route('stock-receipts.store'), $payload)
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($admin)->get(route('stock-receipts.show', $otherReceipt))->assertForbidden();
        $this->assertDatabaseCount('branch_stocks', 0);
    }

    public function test_final_documents_are_immutable_and_have_no_mutation_routes_or_actions(): void
    {
        $branch = $this->createBranch('IMM');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct();
        $receipt = $this->createReceipt($branch, $owner, $product);

        $this->assertFalse(Gate::forUser($owner)->allows('update', $receipt));
        $this->assertFalse(Gate::forUser($owner)->allows('delete', $receipt));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $receipt));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $receipt));
        $this->assertFalse(app('router')->has('stock-receipts.edit'));
        $this->assertFalse(app('router')->has('stock-receipts.update'));
        $this->assertFalse(app('router')->has('stock-receipts.destroy'));

        $this->actingAs($owner)->get(route('stock-receipts.show', $receipt))
            ->assertOk()
            ->assertDontSee('>Edit<', false)
            ->assertDontSee('>Hapus<', false);

        $product->update(['is_active' => false]);
        $this->actingAs($owner)->get(route('stock-receipts.show', $receipt))->assertOk();
        $this->assertTrue(StockReceipt::query()->whereKey($receipt->id)->exists());
    }
}
