<?php

namespace Tests\Feature\Receipt;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Tests\Feature\Sale\SaleTestCase;

class ReceiptPrintIntegrationTest extends SaleTestCase
{
    public function test_print_checkout_returns_new_sale_receipt_url_and_print_page_is_read_only(): void
    {
        $branch = $this->createBranch('PRT');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '5.000');
        $payment = $this->createPaymentMethod();

        $response = $this->actingAs($cashier)->postJson(
            route('cashier.checkout.store'),
            $this->payload($cashier, $branch, $product, $payment, [
                'payment_action' => 'print',
            ]),
        )->assertCreated()
            ->assertJsonPath('data.print_available', true);
        $sale = Sale::query()->sole();
        $printUrl = route('receipts.print', $sale);
        $response
            ->assertJsonPath('data.print_url', $printUrl)
            ->assertJsonPath('data.invoice_number', $sale->invoice_number);
        $counts = [
            'sales' => Sale::query()->count(),
            'items' => SaleItem::query()->count(),
            'movements' => StockMovement::query()->count(),
            'stock' => $stock->fresh()->quantity,
        ];

        $this->get($printUrl)->assertOk()->assertSee($sale->invoice_number);
        $this->assertSame($counts['sales'], Sale::query()->count());
        $this->assertSame($counts['items'], SaleItem::query()->count());
        $this->assertSame($counts['movements'], StockMovement::query()->count());
        $this->assertSame($counts['stock'], $stock->fresh()->quantity);
    }

    public function test_no_print_checkout_returns_no_url_and_idempotent_print_uses_same_sale(): void
    {
        $branch = $this->createBranch('IDM');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $this->createStock($branch, $product, '10.000');
        $payment = $this->createPaymentMethod();
        $noPrintPayload = $this->payload($cashier, $branch, $product, $payment, [
            'payment_action' => 'no_print',
        ]);

        $this->actingAs($cashier)->postJson(route('cashier.checkout.store'), $noPrintPayload)
            ->assertCreated()
            ->assertJsonPath('data.print_available', false)
            ->assertJsonPath('data.print_url', null);

        $printPayload = $this->payload($cashier, $branch, $product, $payment, [
            'payment_action' => 'print',
        ]);
        $first = $this->postJson(route('cashier.checkout.store'), $printPayload)->assertCreated();
        $second = $this->postJson(route('cashier.checkout.store'), $printPayload)->assertOk();

        $this->assertSame($first->json('data.sale_id'), $second->json('data.sale_id'));
        $this->assertSame($first->json('data.print_url'), $second->json('data.print_url'));
        $this->assertSame(
            route('receipts.print', ['sale' => $first->json('data.sale_id')]),
            $first->json('data.print_url'),
        );
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('sale_items', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_cashier_frontend_preopens_receipt_window_and_provides_popup_fallback(): void
    {
        $checkoutScript = file_get_contents(
            public_path('assets/js/pages/cashier/checkout-client.js'),
        );
        $paymentScript = file_get_contents(
            public_path('assets/js/pages/cashier/payment-form.js'),
        );
        $modal = file_get_contents(
            resource_path('views/pages/cashier/sections/payment-preview-modal.blade.php'),
        );

        $this->assertIsString($checkoutScript);
        $this->assertIsString($paymentScript);
        $this->assertIsString($modal);
        $this->assertStringContainsString(
            "window.open('about:blank', receiptWindowName)",
            $checkoutScript,
        );
        $this->assertStringContainsString('printWindow.location.replace(printUrl)', $checkoutScript);
        $this->assertStringContainsString('closePrintWindow(printWindow)', $checkoutScript);
        $this->assertStringContainsString('payload.data?.print_available === true', $checkoutScript);
        $this->assertStringContainsString('paymentForm.showSuccess', $checkoutScript);
        $this->assertStringContainsString('printFallbackRequired', $checkoutScript);
        $this->assertStringContainsString('data-preview-print-link', $paymentScript);
        $this->assertStringContainsString('data-preview-print-link', $modal);
        $this->assertStringContainsString('Buka Struk untuk Dicetak', $modal);
        $this->assertStringContainsString('target="receipt-print"', $modal);
        $this->assertStringNotContainsString('target="_blank"', $modal);
        $this->assertStringNotContainsString('window.print()', $checkoutScript);
    }
}
