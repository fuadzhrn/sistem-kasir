<?php

namespace Tests\Feature\StoreSetting;

use App\Models\ActivityLog;

class StoreSettingDataSecurityTest extends StoreSettingTestCase
{
    public function test_page_and_audit_do_not_expose_credentials_or_arbitrary_keys(): void
    {
        $owner = $this->createUser('owner');
        $response = $this->actingAs($owner)->get(route('settings.store.index'));

        $response->assertOk()
            ->assertDontSee('APP_KEY')
            ->assertDontSee('DB_PASSWORD')
            ->assertDontSee('custom HTML');

        $this->actingAs($owner)->put(route('settings.store.general.update'), [
            'store_name' => 'Toko Aman',
            'APP_KEY' => 'secret',
            'password' => 'rahasia',
        ]);
        $this->assertDatabaseMissing('settings', ['key' => 'APP_KEY']);
        $this->assertDatabaseMissing('settings', ['key' => 'password']);
        $this->assertStringNotContainsString(
            'rahasia',
            json_encode(
                ActivityLog::query()->latest('id')->first()?->metadata,
                JSON_THROW_ON_ERROR,
            ),
        );
    }
}
