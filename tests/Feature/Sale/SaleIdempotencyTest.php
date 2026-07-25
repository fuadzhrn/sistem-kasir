<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use Illuminate\Database\QueryException;

class SaleIdempotencyTest extends SaleTestCase
{
    public function test_same_token_same_actor_and_branch_returns_original_sale_once(): void
    {
        $branch = $this->createBranch('IDEM');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '10.000');
        $payment = $this->createPaymentMethod();
        $payload = $this->payload($cashier, $branch, $product, $payment);

        $first = $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('idempotent', false);
        $second = $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertOk()
            ->assertJsonPath('idempotent', true);

        $this->assertSame(
            $first->json('data.sale_id'),
            $second->json('data.sale_id'),
        );
        $this->assertSame(
            $first->json('data.invoice_number'),
            $second->json('data.invoice_number'),
        );
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertSame('8.000', $stock->refresh()->quantity);
    }

    public function test_same_token_from_different_user_or_branch_is_rejected(): void
    {
        $branchA = $this->createBranch('IDA');
        $branchB = $this->createBranch('IDB');
        $cashierA = $this->createUser('cashier', $branchA);
        $otherA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);
        $product = $this->createProduct();
        $this->createStock($branchA, $product, '10.000');
        $this->createStock($branchB, $product, '10.000');
        $payment = $this->createPaymentMethod();
        $token = $this->nextToken();

        $this->actingAs($cashierA)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashierA, $branchA, $product, $payment, [
                'checkout_token' => $token,
            ]),
        )->assertCreated();

        foreach ([[$otherA, $branchA], [$cashierB, $branchB]] as [$user, $branch]) {
            $this->actingAs($user)->postJson(
                route('cashier.checkout.store'),
                $this->payload($user, $branch, $product, $payment, [
                    'checkout_token' => $token,
                ]),
            )->assertConflict()->assertJsonPath('code', 'DUPLICATE_CHECKOUT_TOKEN');
        }

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_different_tokens_create_distinct_transactions(): void
    {
        $branch = $this->createBranch('TOK');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000');
        $payment = $this->createPaymentMethod();

        for ($index = 0; $index < 2; $index++) {
            $this->actingAs($owner)->postJson(
                route('cashier.checkout.store'),
                $this->payload($owner, $branch, $product, $payment),
            )->assertCreated();
        }

        $this->assertDatabaseCount('sales', 2);
    }

    public function test_database_unique_constraint_is_final_checkout_token_protection(): void
    {
        $branch = $this->createBranch('UNIQ');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertCreated();
        $sale = Sale::query()->sole();
        $duplicate = $sale->replicate(['invoice_number']);
        $duplicate->invoice_number = 'INV-UNIQ-20990101-9999';

        $this->expectException(QueryException::class);

        $duplicate->save();
    }
}
