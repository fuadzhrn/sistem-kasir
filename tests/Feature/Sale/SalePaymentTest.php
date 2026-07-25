<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;

class SalePaymentTest extends SaleTestCase
{
    public function test_cash_requires_sufficient_amount_and_calculates_change(): void
    {
        $branch = $this->createBranch('CASH');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product);
        $cash = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $cash, [
                'amount_received' => '39999.99',
            ]),
        )->assertUnprocessable()->assertJsonPath('code', 'INSUFFICIENT_PAYMENT');
        $this->assertSame('10.000', $stock->refresh()->quantity);

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $cash, [
                'amount_received' => '50000.00',
            ]),
        )->assertCreated()
            ->assertJsonPath('data.amount_paid', '50000.00')
            ->assertJsonPath('data.change_amount', '10000.00');
    }

    public function test_non_cash_and_other_ignore_client_amount_received(): void
    {
        foreach (['non_cash', 'other'] as $index => $type) {
            $branch = $this->createBranch('NC'.$index);
            $owner = $this->createUser('owner');
            $product = $this->createProduct();
            $this->createStock($branch, $product);
            $payment = $this->createPaymentMethod([
                'code' => 'NC'.$index,
                'name' => $type === 'non_cash' ? 'Transfer Bank' : 'Lainnya',
                'type' => $type,
            ]);

            $this->actingAs($owner)->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment, [
                    'amount_received' => '1.00',
                ]),
            )->assertCreated()
                ->assertJsonPath('data.amount_paid', '40000.00')
                ->assertJsonPath('data.change_amount', '0.00');
        }
    }

    public function test_inactive_or_unknown_payment_method_is_rejected(): void
    {
        $branch = $this->createBranch('PM');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product);
        $inactive = $this->createPaymentMethod(['is_active' => false]);

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $inactive),
        )->assertUnprocessable()->assertJsonPath('code', 'PAYMENT_METHOD_INACTIVE');

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $inactive, [
                'payment_method_id' => 999999,
            ]),
        )->assertUnprocessable()->assertJsonPath('code', 'PAYMENT_METHOD_INACTIVE');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('10.000', $stock->refresh()->quantity);
    }

    public function test_payment_method_name_is_an_immutable_snapshot(): void
    {
        $branch = $this->createBranch('SNAP');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod(['name' => 'Tunai Awal']);

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertCreated();
        $payment->update(['name' => 'Tunai Baru']);

        $this->assertSame('Tunai Awal', Sale::query()->sole()->payment_method_name);
    }
}
