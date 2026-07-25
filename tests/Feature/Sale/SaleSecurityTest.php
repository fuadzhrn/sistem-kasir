<?php

namespace Tests\Feature\Sale;

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;

class SaleSecurityTest extends SaleTestCase
{
    public function test_client_controlled_business_fields_are_ignored(): void
    {
        $branch = $this->createBranch('SEC');
        $cashier = $this->createUser('cashier', $branch);
        $other = $this->createUser('cashier', $branch);
        $product = $this->createProduct(['selling_price' => '20000.00']);
        $this->createStock($branch, $product, '10.000', '12500.00');
        $payment = $this->createPaymentMethod();
        $payload = $this->payload($cashier, $branch, $product, $payment, [
            'cashier_id' => $other->id,
            'selling_price' => '1.00',
            'cost_price' => '1.00',
            'subtotal' => '1.00',
            'total_cost' => '1.00',
            'gross_profit' => '999999.00',
            'invoice_number' => 'INV-HACKED',
            'transaction_date' => '2000-01-01',
            'status' => Sale::STATUS_VOIDED,
            'quantity_before' => '999.000',
            'movement_type' => StockMovement::TYPE_VOID_SALE,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => '2.000',
                'selling_price' => '1.00',
                'cost_price' => '1.00',
                'subtotal' => '2.00',
                'profit' => '999999.00',
            ]],
        ]);

        $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), $payload)
            ->assertCreated();

        $sale = Sale::query()->sole();
        $movement = StockMovement::query()->sole();
        $this->assertSame($cashier->id, $sale->cashier_id);
        $this->assertSame($branch->id, $sale->branch_id);
        $this->assertSame('40000.00', $sale->subtotal);
        $this->assertSame('25000.00', $sale->total_cost);
        $this->assertSame('15000.00', $sale->gross_profit);
        $this->assertNotSame('INV-HACKED', $sale->invoice_number);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame(StockMovement::TYPE_SALE, $movement->movement_type);
        $this->assertSame('10.000', $movement->quantity_before);
    }

    public function test_success_and_domain_error_responses_never_expose_cost_or_internal_data(): void
    {
        $branch = $this->createBranch('SAFE');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000', '87654.32');
        $payment = $this->createPaymentMethod();

        $response = $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment),
        )->assertCreated();
        $content = mb_strtolower($response->getContent());

        foreach ([
            'cost_price',
            'total_cost',
            'gross_profit',
            'average_cost',
            'unit_cost',
            'purchase_price',
            'stock_movements',
            'activity_logs',
            '87654.32',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $content);
        }
    }

    public function test_checkout_requires_post_web_csrf_auth_role_and_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('cashier.checkout.store');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('web', $route->middleware());
        $this->assertContains('auth', $route->middleware());
        $this->assertContains('active.user', $route->middleware());
        $this->assertContains('role:owner,admin,cashier', $route->middleware());
        $this->assertContains('throttle:60,1', $route->middleware());
        $this->getJson('/cashier/checkout')->assertMethodNotAllowed();
    }

    public function test_rate_limit_rejects_request_after_sixty_attempts(): void
    {
        $branch = $this->createBranch('RATE');
        $cashier = $this->createUser('cashier', $branch);

        for ($request = 1; $request <= 60; $request++) {
            $this->actingAs($cashier)
                ->postJson(route('cashier.checkout.store'), [])
                ->assertUnprocessable();
        }

        $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), [])
            ->assertTooManyRequests();
    }

    public function test_checkout_token_format_and_duplicate_products_are_strictly_validated(): void
    {
        $branch = $this->createBranch('TOKEN');
        $owner = $this->createUser('owner');
        $product = $this->createProduct();
        $this->createStock($branch, $product);
        $payment = $this->createPaymentMethod();

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'checkout_token' => 'token dengan spasi dan rahasia',
            ]),
        )->assertUnprocessable()->assertJsonValidationErrors('checkout_token');

        $this->actingAs($owner)->postJson(
            route('cashier.checkout.store'),
            $this->payload($owner, $branch, $product, $payment, [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => '1.000'],
                    ['product_id' => $product->id, 'quantity' => '1.000'],
                ],
            ]),
        )->assertUnprocessable()->assertJsonValidationErrors('items.1.product_id');
    }

    public function test_frontend_checkout_contract_has_csrf_idempotency_loading_and_no_print_call(): void
    {
        $checkout = file_get_contents(public_path('assets/js/pages/cashier/checkout-client.js'));
        $payment = file_get_contents(public_path('assets/js/pages/cashier/payment-form.js'));
        $combined = $checkout."\n".$payment;

        $this->assertStringContainsString("method: 'POST'", $checkout);
        $this->assertStringContainsString("'X-CSRF-TOKEN'", $checkout);
        $this->assertStringContainsString('checkout_token', $checkout);
        $this->assertStringContainsString('product_id: item.product_id', $checkout);
        $this->assertStringContainsString('quantity: item.quantity', $checkout);
        $this->assertStringContainsString('if (isSubmitting)', $checkout);
        $this->assertStringContainsString('store.clear()', $checkout);
        $this->assertStringContainsString("action === 'print'", $payment);
        $this->assertStringContainsString('data-payment-action="no_print"', file_get_contents(
            resource_path('views/pages/cashier/sections/payment-form.blade.php'),
        ));
        $this->assertStringNotContainsString('window.print', $combined);
        $this->assertStringNotContainsString('cost_price', $checkout);
        $this->assertStringNotContainsString('gross_profit', $checkout);
    }
}
