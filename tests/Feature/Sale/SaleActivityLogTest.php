<?php

namespace Tests\Feature\Sale;

use App\Models\ActivityLog;
use App\Models\Sale;

class SaleActivityLogTest extends SaleTestCase
{
    public function test_successful_sale_creates_safe_activity_log_in_transaction(): void
    {
        $branch = $this->createBranch('LOG');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();
        $token = $this->nextToken();
        $payload = $this->payload($cashier, $branch, $product, $payment, [
            'checkout_token' => $token,
            'password' => 'rahasia-yang-tidak-boleh-masuk-log',
        ]);

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.8',
            'HTTP_USER_AGENT' => 'Browser Pengujian Tahap 13',
        ])->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertCreated();

        $sale = Sale::query()->sole();
        $log = ActivityLog::query()->sole();
        $this->assertSame($cashier->id, $log->user_id);
        $this->assertSame($branch->id, $log->branch_id);
        $this->assertSame('sale_created', $log->action);
        $this->assertSame('sales', $log->module);
        $this->assertSame(Sale::class, $log->reference_type);
        $this->assertSame($sale->id, $log->reference_id);
        $this->assertStringContainsString($sale->invoice_number, $log->description);
        $this->assertSame('127.0.0.8', $log->ip_address);
        $this->assertStringContainsString('Browser Pengujian', $log->user_agent);
        $this->assertStringNotContainsString($token, $log->description);
        $this->assertStringNotContainsString('rahasia', mb_strtolower($log->description));
        $this->assertStringNotContainsString('12500', $log->description);
    }

    public function test_failed_sale_does_not_create_activity_log(): void
    {
        $branch = $this->createBranch('NOLOG');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '1.000');
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertConflict();

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
