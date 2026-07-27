<?php

namespace Tests\Feature\StoreSetting;

use App\Models\PaymentMethod;

class PaymentMethodSettingIntegrationTest extends StoreSettingTestCase
{
    public function test_page_summarizes_existing_payment_method_table_without_duplicate_setting(): void
    {
        PaymentMethod::factory()->create(['name' => 'Tunai Aktif', 'is_active' => true, 'sort_order' => 2]);
        PaymentMethod::factory()->create(['name' => 'Transfer Mati', 'is_active' => false]);

        $this->actingAs($this->createUser('owner'))
            ->get(route('settings.store.index'))
            ->assertOk()
            ->assertSee('Tunai Aktif')
            ->assertSee(route('payment-methods.index'))
            ->assertDontSee('Transfer Mati');

        $this->assertDatabaseMissing('settings', ['key' => 'store.payment_methods']);
    }
}
