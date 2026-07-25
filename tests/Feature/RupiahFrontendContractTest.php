<?php

namespace Tests\Feature;

use Tests\TestCase;

class RupiahFrontendContractTest extends TestCase
{
    public function test_javascript_money_formatters_use_indonesian_locale_without_decimals(): void
    {
        $cashier = (string) file_get_contents(
            public_path('assets/js/pages/cashier/cashier-utils.js'),
        );
        $products = (string) file_get_contents(public_path('assets/js/pages/products.js'));
        $stockReceipts = (string) file_get_contents(
            public_path('assets/js/pages/stock-receipts.js'),
        );

        foreach ([$cashier, $products, $stockReceipts] as $script) {
            $this->assertStringContainsString("Intl.NumberFormat('id-ID'", $script);
            $this->assertStringContainsString('minimumFractionDigits: 0', $script);
            $this->assertStringContainsString('maximumFractionDigits: 0', $script);
            $this->assertStringNotContainsString("style: 'currency'", $script);
            $this->assertStringNotContainsString("currency: 'IDR'", $script);
        }

        $this->assertStringNotContainsString('Number.parseFloat', $stockReceipts);
        $this->assertStringContainsString("return 'Rp' + result", $cashier);
        $this->assertStringContainsString("normalized.replaceAll('.', '')", $cashier);
    }

    public function test_money_inputs_are_text_fields_with_numeric_keyboard_hints(): void
    {
        $productFields = (string) file_get_contents(
            resource_path('views/pages/products/sections/product-price-fields.blade.php'),
        );
        $stockReceiptRow = (string) file_get_contents(
            resource_path('views/pages/stock-receipts/sections/receipt-item-row.blade.php'),
        );
        $cashierPayment = (string) file_get_contents(
            resource_path('views/pages/cashier/sections/payment-form.blade.php'),
        );
        $moneyInputs = $productFields.$stockReceiptRow.$cashierPayment;

        $this->assertSame(5, substr_count($moneyInputs, 'data-rupiah-input'));
        $this->assertGreaterThanOrEqual(5, substr_count($moneyInputs, 'type="text"'));
        $this->assertGreaterThanOrEqual(5, substr_count($moneyInputs, 'inputmode="numeric"'));
        $this->assertStringNotContainsString('step="0.01"', $moneyInputs);
    }
}
