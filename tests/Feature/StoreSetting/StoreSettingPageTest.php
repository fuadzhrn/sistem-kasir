<?php

namespace Tests\Feature\StoreSetting;

class StoreSettingPageTest extends StoreSettingTestCase
{
    public function test_owner_can_open_modular_store_setting_page(): void
    {
        $this->actingAs($this->createUser('owner'))
            ->get(route('settings.store.index'))
            ->assertOk()
            ->assertSee('Pengaturan Toko')
            ->assertSee('Informasi Toko')
            ->assertSee('Metode Pembayaran')
            ->assertSee('assets/css/pages/store-settings.css')
            ->assertSee('assets/js/pages/store-settings.js');
    }

    public function test_opening_page_does_not_create_activity_log(): void
    {
        $this->actingAs($this->createUser('owner'))->get(route('settings.store.index'))->assertOk();
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
