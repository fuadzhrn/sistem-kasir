<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;

class SaleDiscountTest extends SaleTestCase
{
    public function test_owner_and_admin_can_discount_up_to_subtotal(): void
    {
        foreach (['owner', 'admin'] as $index => $role) {
            $branch = $this->createBranch('DISC'.$index);
            $user = $this->createUser($role, $role === 'owner' ? null : $branch);
            $product = $this->createProduct();
            $this->createStock($branch, $product);
            $payment = $this->createPaymentMethod(['code' => 'D'.$index]);

            $this->actingAs($user)->postJson(
                route('cashier.checkout.store'),
                $this->payload($user, $branch, $product, $payment, [
                    'discount_amount' => '40000.00',
                    'amount_received' => '0.00',
                    'expected_total' => '0.00',
                ]),
            )->assertCreated()
                ->assertJsonPath('data.total', '0.00')
                ->assertJsonPath('data.change_amount', '0.00');
        }
    }

    public function test_discount_above_subtotal_and_negative_discount_are_rejected(): void
    {
        $branch = $this->createBranch('BAD');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'discount_amount' => '40000.01',
                'expected_total' => '0.00',
            ]),
        )->assertUnprocessable()->assertJsonPath('code', 'INVALID_DISCOUNT');

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'discount_amount' => '-1.00',
            ]),
        )->assertUnprocessable()->assertJsonPath('code', 'INVALID_DISCOUNT');
    }

    public function test_cashier_limit_comes_from_database_and_missing_setting_means_zero(): void
    {
        $branch = $this->createBranch('LIMIT');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000');
        $payment = $this->createPaymentMethod();

        $this->actingAs($cashier)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashier, $branch, $product, $payment, [
                'discount_amount' => '1.00',
                'expected_total' => '39999.00',
            ]),
        )->assertUnprocessable()->assertJsonPath('code', 'DISCOUNT_LIMIT_EXCEEDED');

        $this->setCashierDiscount('5000.00');
        $this->actingAs($cashier)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashier, $branch, $product, $payment, [
                'discount_amount' => '5000.00',
                'expected_total' => '35000.00',
            ]),
        )->assertCreated()->assertJsonPath('data.discount_amount', '5000.00');

        $this->actingAs($cashier)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashier, $branch, $product, $payment, [
                'discount_amount' => '5001',
                'expected_total' => '34999.00',
            ]),
        )->assertUnprocessable()->assertJsonPath('code', 'DISCOUNT_LIMIT_EXCEEDED');
    }

    public function test_discount_allocation_net_subtotal_profit_and_header_are_consistent(): void
    {
        $branch = $this->createBranch('ALLOC');
        $owner = $this->createUser('owner');
        $productA = $this->createProduct(['code' => 'A', 'selling_price' => '10000.00']);
        $productB = $this->createProduct(['code' => 'B', 'selling_price' => '30000.00']);
        $this->createStock($branch, $productA, '10.000', '5000.00');
        $this->createStock($branch, $productB, '10.000', '10000.00');
        $payment = $this->createPaymentMethod();
        $payload = $this->payload($owner, $branch, $productA, $payment, [
            'items' => [
                ['product_id' => $productA->id, 'quantity' => '1.000'],
                ['product_id' => $productB->id, 'quantity' => '1.000'],
            ],
            'discount_amount' => '4000.00',
            'expected_subtotal' => '40000.00',
            'expected_total' => '36000.00',
        ]);

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertCreated();

        $sale = Sale::query()->with('items')->sole();
        $items = $sale->items->sortBy('product_id')->values();
        $this->assertSame(['1000.00', '3000.00'], $items->pluck('discount_amount')->all());
        $this->assertSame(['9000.00', '27000.00'], $items->pluck('subtotal')->all());
        $this->assertSame(['4000.00', '17000.00'], $items->pluck('profit')->all());
        $this->assertSame('15000.00', $sale->total_cost);
        $this->assertSame('21000.00', $sale->gross_profit);
    }
}
