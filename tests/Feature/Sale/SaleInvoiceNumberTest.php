<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use Illuminate\Support\Carbon;

class SaleInvoiceNumberTest extends SaleTestCase
{
    public function test_invoice_format_sequence_branch_and_date_are_server_controlled(): void
    {
        Carbon::setTestNow('2026-07-25 09:30:00');
        $branchA = $this->createBranch('UT-M');
        $branchB = $this->createBranch('CBG01');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branchA, $product, '10.000');
        $this->createStock($branchB, $product, '10.000');
        $payment = $this->createPaymentMethod();

        foreach ([$branchA, $branchA, $branchB] as $branch) {
            $this->actingAs($owner)->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment, [
                    'invoice_number' => 'INV-PALSU-0000',
                ]),
            )->assertCreated();
        }

        $this->assertSame([
            'UTM-20260725-0001',
            'UTM-20260725-0002',
            'CBG01-20260725-0001',
        ], Sale::query()->orderBy('id')->pluck('invoice_number')->all());
        $this->assertSame(
            3,
            Sale::query()->distinct()->count('invoice_number'),
        );
        Carbon::setTestNow();
    }

    public function test_next_date_starts_new_sequence_without_changing_old_invoice(): void
    {
        $branch = $this->createBranch('DATE');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000');
        $payment = $this->createPaymentMethod();
        Carbon::setTestNow('2026-07-25 23:59:00');
        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertCreated();
        $first = Sale::query()->sole();

        Carbon::setTestNow('2026-07-26 00:01:00');
        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertCreated();

        $this->assertSame('DATE-20260725-0001', $first->refresh()->invoice_number);
        $this->assertSame(
            'DATE-20260726-0001',
            Sale::query()->latest('id')->value('invoice_number'),
        );
        Carbon::setTestNow();
    }
}
