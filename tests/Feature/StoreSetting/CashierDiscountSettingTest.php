<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Setting;
use Tests\Feature\Sale\SaleTestCase;

class CashierDiscountSettingTest extends SaleTestCase
{
    public function test_cashier_limit_is_enforced_but_owner_may_discount_to_subtotal(): void
    {
        Setting::query()->create([
            'key' => 'business.maximum_cashier_discount',
            'value' => '10000.00',
            'type' => 'decimal',
            'group' => 'business',
        ]);
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '20.000');
        $payment = $this->createPaymentMethod();

        $this->actingAs($cashier)->postJson(route('cashier.checkout.store'), $this->payload(
            $cashier,
            $branch,
            $product,
            $payment,
            ['discount_amount' => '10001.00', 'expected_total' => '29999.00'],
        ))->assertStatus(422)->assertJsonPath('code', 'CASHIER_DISCOUNT_LIMIT_EXCEEDED');
        $this->assertDatabaseCount('sales', 0);

        $this->actingAs($owner)->postJson(route('cashier.checkout.store'), $this->payload(
            $owner,
            $branch,
            $product,
            $payment,
            ['discount_amount' => '40000.00', 'expected_total' => '0.00'],
        ))->assertCreated();
    }
}
