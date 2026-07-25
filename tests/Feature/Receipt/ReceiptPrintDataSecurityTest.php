<?php

namespace Tests\Feature\Receipt;

class ReceiptPrintDataSecurityTest extends ReceiptPrintTestCase
{
    public function test_receipt_omits_internal_financial_and_secret_data_for_every_role(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'checkout_token' => 'private-stage15-checkout-token',
            'total_cost' => '98765.43',
            'gross_profit' => '71234.57',
        ]);

        foreach ([$owner, $cashier] as $viewer) {
            $response = $this->actingAs($viewer)->get($this->printUrl($sale->id))->assertOk();
            $content = mb_strtolower($response->getContent());

            foreach ([
                'purchase_price',
                'cost_price',
                'total_cost',
                'profit',
                'gross_profit',
                'average_cost',
                'checkout_token',
                'private-stage15-checkout-token',
                'rp98.765,43',
                'rp71.234,57',
                'rp39.506,17',
                'stock movement',
                'activity log',
                'password',
                'session id',
            ] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $content);
            }
        }
    }

    public function test_untrusted_receipt_text_is_escaped_and_response_is_not_cached_or_indexed(): void
    {
        $branch = $this->createBranch('AAA', [
            'address' => '<script>alert("alamat")</script>',
        ]);
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'notes' => '<img src=x onerror=alert("catatan")>',
        ]);
        $sale->items()->update(['product_name' => '<script>alert("produk")</script>']);

        $response = $this->actingAs($owner)->get($this->printUrl($sale->id))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert', false)
            ->assertSee('&lt;img src=x', false)
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('<img src=x', false)
            ->assertHeader('Cache-Control')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        foreach (['private', 'no-store', 'no-cache', 'must-revalidate'] as $directive) {
            $this->assertStringContainsString(
                $directive,
                (string) $response->headers->get('Cache-Control'),
            );
        }
    }
}
