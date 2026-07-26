<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use Tests\Feature\Sale\SaleTestCase;

class ActivityLogSaleTest extends SaleTestCase
{
    public function test_successful_checkout_has_exactly_one_sale_created_log(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $this->payload($cashier, $branch, $product, $payment))
            ->assertCreated();

        $this->assertSame(1, ActivityLog::query()->where('action', 'sale_created')->count());
    }
}
