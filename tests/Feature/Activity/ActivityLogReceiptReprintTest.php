<?php

namespace Tests\Feature\Activity;

use App\Models\PaymentMethod;
use App\Models\Sale;

class ActivityLogReceiptReprintTest extends ActivityLogTestCase
{
    public function test_get_print_is_read_only_and_post_reprint_is_audited(): void
    {
        $branch = $this->branch();
        $owner = $this->user('owner');
        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $owner->id,
            'payment_method_id' => $payment->id,
        ]);

        $this->actingAs($owner)->get(route('receipts.print', $sale))->assertOk();
        $this->assertDatabaseMissing('activity_logs', ['action' => 'receipt_reprint_requested']);

        $this->actingAs($owner)
            ->post(route('sales.receipt.reprint', $sale))
            ->assertRedirect(route('receipts.print', ['sale' => $sale->id, 'copy' => 1]));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'receipt_reprint_requested',
            'branch_id' => $branch->id,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_admin_cannot_reprint_other_branch_sale_by_url_manipulation(): void
    {
        $branchA = $this->branch('RA1');
        $branchB = $this->branch('RB1');
        $admin = $this->user('admin', $branchA);
        $sale = Sale::factory()->create(['branch_id' => $branchB->id]);

        $this->actingAs($admin)->post(route('sales.receipt.reprint', $sale))->assertNotFound();
        $this->assertDatabaseMissing('activity_logs', ['action' => 'receipt_reprint_requested']);
    }
}
