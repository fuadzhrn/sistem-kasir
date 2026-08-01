<?php

namespace Tests\Feature\Receipt;

class ReceiptPrintMarkupTest extends ReceiptPrintTestCase
{
    public function test_print_markup_is_local_minimal_and_has_no_inline_handlers(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');

        $content = $this->actingAs($owner)
            ->get($this->printUrl($sale->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="receipt ', $content);
        $this->assertStringContainsString('receipt-paper--80', $content);
        $this->assertStringContainsString('class="receipt-toolbar print-hidden"', $content);
        $this->assertStringContainsString('data-receipt-print-button', $content);
        $this->assertStringContainsString('data-receipt-close-button', $content);
        $this->assertStringContainsString('data-receipt-window-name="receipt-print"', $content);
        $this->assertStringContainsString('data-receipt-paper-select', $content);
        $this->assertStringContainsString('meta name="viewport"', $content);
        $this->assertStringContainsString('meta name="robots" content="noindex,nofollow"', $content);
        $this->assertStringContainsString('Struk AAA-20260724-0001', $content);
        $this->assertStringNotContainsString('app-sidebar', $content);
        $this->assertStringNotContainsString('app-navbar', $content);
        $this->assertStringNotContainsString('fonts.googleapis.com', $content);
        $this->assertStringNotContainsString('Poppins', $content);
        $this->assertStringNotContainsString('Montserrat', $content);
        $this->assertStringNotContainsString('onclick=', $content);
        $this->assertStringNotContainsString('window.print()', $content);
    }

    public function test_css_and_javascript_contain_required_print_profiles_and_guards(): void
    {
        $css = file_get_contents(public_path('assets/css/print/receipt.css'));
        $javascript = file_get_contents(public_path('assets/js/pages/receipt.js'));
        $blade = file_get_contents(resource_path('views/pages/receipts/print.blade.php'));

        foreach ([
            'font-family: Arial',
            '.receipt-paper--58',
            '.receipt-paper--80',
            '--receipt-paper-width: 58mm',
            '--receipt-paper-width: 80mm',
            '@media screen',
            '@media print',
            '@page',
            'page-break-inside: avoid',
        ] as $requiredCss) {
            $this->assertStringContainsString($requiredCss, $css);
        }

        foreach ([
            'window.print()',
            'hasAutoPrinted',
            "window.addEventListener('afterprint'",
            'isDedicatedReceiptWindow',
            'window.close()',
            'document.fonts?.ready',
            "querySelectorAll('img')",
            'receipt_paper_width',
            "allowedPaperWidths = ['58', '80']",
            'window.localStorage.setItem',
        ] as $requiredJavascript) {
            $this->assertStringContainsString($requiredJavascript, $javascript);
        }

        $this->assertStringNotContainsString('onclick=', $blade);
        $this->assertStringNotContainsString('window.print', $blade);
    }
}
