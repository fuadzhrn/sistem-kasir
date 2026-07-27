<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use App\Models\SaleItem;

class SaleCheckoutTest extends SaleTestCase
{
    public function test_owner_admin_and_cashier_can_checkout_for_authorized_branch(): void
    {
        foreach (['owner', 'admin', 'cashier'] as $index => $role) {
            $branch = $this->createBranch('R'.$index);
            $user = $this->createUser($role, $role === 'owner' ? null : $branch);
            $product = $this->createProduct();
            $this->createStock($branch, $product);
            $payment = $this->createPaymentMethod(['code' => 'CASH'.$index]);

            $response = $this->actingAs($user)->postJson(
                route('cashier.checkout.store'),
                $this->payload($user, $branch, $product, $payment),
            );

            $response->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath('idempotent', false)
                ->assertJsonPath('data.branch_name', $branch->name)
                ->assertJsonPath('data.item_count', 1)
                ->assertJsonPath('data.payment_action', 'no_print')
                ->assertJsonPath('data.print_available', false)
                ->assertJsonMissingPath('data.total_cost')
                ->assertJsonMissingPath('data.gross_profit');
        }

        $this->assertDatabaseCount('sales', 3);
        $this->assertDatabaseCount('sale_items', 3);
        $this->assertSame(
            [Sale::STATUS_COMPLETED],
            Sale::query()->pluck('status')->unique()->values()->all(),
        );
    }

    public function test_checkout_uses_server_actor_branch_date_and_completed_status(): void
    {
        $branch = $this->createBranch('SRV');
        $cashier = $this->createUser('cashier', $branch);
        $other = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        $before = now()->startOfSecond();
        $payload = $this->payload($cashier, $branch, $product, $payment, [
            'cashier_id' => $other->id,
            'transaction_date' => '2000-01-01 00:00:00',
            'status' => Sale::STATUS_VOIDED,
        ]);

        $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertCreated();

        $sale = Sale::query()->sole();
        $this->assertTrue($sale->cashier->is($cashier));
        $this->assertTrue($sale->branch->is($branch));
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertTrue($sale->transaction_date->greaterThanOrEqualTo($before));
        $this->assertNull($sale->voided_at);
    }

    public function test_checkout_validates_required_and_bounded_cart_fields(): void
    {
        $branch = $this->createBranch('VAL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([
                'checkout_token',
                'branch_id',
                'items',
                'payment_method_id',
                'payment_action',
            ]);

        $tooManyItems = array_fill(0, 101, [
            'product_id' => $product->id,
            'quantity' => '1.000',
        ]);
        $this->actingAs($owner)
            ->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment, ['items' => $tooManyItems]),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_quantity_accepts_three_decimals_and_rejects_zero_or_four_decimals(): void
    {
        $branch = $this->createBranch('QTY');
        $owner = $this->createUser('owner');
        $product = $this->createProduct(['selling_price' => '20000.00']);
        $this->createStock($branch, $product, '5.000');
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'items' => [['product_id' => $product->id, 'quantity' => '1,5']],
                'expected_subtotal' => '30000.00',
                'expected_total' => '30000.00',
            ]),
        )->assertCreated();
        $this->assertSame('1.500', SaleItem::query()->sole()->quantity);

        foreach (['0', '1.0001'] as $quantity) {
            $this->actingAs($owner)->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment, [
                    'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
                ]),
            )->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        }
    }

    public function test_print_action_returns_receipt_url_and_no_print_action_does_not(): void
    {
        $branch = $this->createBranch('ACT');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000');
        $payment = $this->createPaymentMethod();

        foreach (['print', 'no_print'] as $action) {
            $response = $this->actingAs($owner)->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment, [
                    'payment_action' => $action,
                ]),
            )->assertCreated()->assertJsonPath('data.payment_action', $action);

            $sale = Sale::query()->latest('id')->firstOrFail();

            if ($action === 'print') {
                $response
                    ->assertJsonPath('data.print_available', true)
                    ->assertJsonPath('data.print_url', route('receipts.print', $sale));
            } else {
                $response
                    ->assertJsonPath('data.print_available', false)
                    ->assertJsonPath('data.print_url', null);
            }
        }

        $this->assertDatabaseCount('sales', 2);
    }
}
