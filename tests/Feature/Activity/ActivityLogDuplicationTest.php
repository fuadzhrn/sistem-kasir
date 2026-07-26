<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use Tests\Feature\Sale\SaleTestCase;

class ActivityLogDuplicationTest extends SaleTestCase
{
    public function test_replayed_checkout_token_does_not_duplicate_sale_audit(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        $payload = $this->payload($cashier, $branch, $product, $payment, [
            'checkout_token' => 'audit-idempotent-token-000000000001',
        ]);

        $this->actingAs($cashier)->postJson(route('cashier.checkout.store'), $payload)->assertCreated();
        $this->actingAs($cashier)->postJson(route('cashier.checkout.store'), $payload)->assertOk();

        $this->assertSame(1, ActivityLog::query()->where('action', 'sale_created')->count());
    }
}
