<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use Carbon\Carbon;

class SaleReceiptNumberTest extends SaleTestCase
{
    public function test_new_receipt_number_uses_final_format_without_inv_prefix(): void
    {
        Carbon::setTestNow('2026-07-24 09:00:00');
        $branch = $this->createBranch('UTM');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $this->payload(
                $cashier,
                $branch,
                $product,
                $payment,
            ))
            ->assertCreated()
            ->assertJsonPath('data.invoice_number', 'UTM-20260724-0001');

        $this->assertDoesNotMatchRegularExpression('/^INV-/', Sale::query()->value('invoice_number'));
        Carbon::setTestNow();
    }

    public function test_old_receipt_number_remains_unchanged_and_is_searchable(): void
    {
        $branch = $this->createBranch('UTM');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $payment = $this->createPaymentMethod();
        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'payment_method_id' => $payment->id,
            'payment_method_name' => $payment->name,
            'invoice_number' => 'INV-UTM-20260723-0042',
        ]);

        $this->actingAs($owner)
            ->get(route('sales.index', ['search' => 'INV-UTM-20260723-0042']))
            ->assertOk()
            ->assertSee('INV-UTM-20260723-0042');

        $this->assertSame('INV-UTM-20260723-0042', $sale->refresh()->invoice_number);
    }
}
