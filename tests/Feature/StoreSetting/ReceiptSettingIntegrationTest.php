<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Setting;
use Tests\Feature\Receipt\ReceiptPrintTestCase;

class ReceiptSettingIntegrationTest extends ReceiptPrintTestCase
{
    public function test_receipt_uses_safe_store_settings_and_escaped_additional_information(): void
    {
        Setting::query()->create(['key' => 'store.name', 'value' => 'Agro Baru', 'type' => 'string', 'group' => 'store']);
        Setting::query()->create(['key' => 'receipt.additional_information', 'value' => '<script>jahat()</script>', 'type' => 'string', 'group' => 'receipt']);
        Setting::query()->create(['key' => 'receipt.show_product_code', 'value' => '0', 'type' => 'boolean', 'group' => 'receipt']);
        Setting::query()->create(['key' => 'receipt.show_transaction_notes', 'value' => '0', 'type' => 'boolean', 'group' => 'receipt']);
        $branch = $this->createBranch('UTM');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $sale = $this->createSale($branch, $cashier, 'UTM-20260724-0001');

        $this->actingAs($owner)
            ->get(route('receipts.print', $sale))
            ->assertOk()
            ->assertSee('Agro Baru')
            ->assertSee('&lt;script&gt;jahat()&lt;/script&gt;', false)
            ->assertDontSee('<script>jahat()</script>', false);
    }
}
