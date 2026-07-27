<?php

namespace Tests\Feature\StoreSetting;

class StoreSettingActivityLogTest extends StoreSettingTestCase
{
    public function test_each_setting_group_creates_one_owner_audit_record(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->put(route('settings.store.general.update'), [
            'store_name' => 'Toko Audit',
            'store_address' => null,
            'store_phone' => null,
        ]);
        $this->actingAs($owner)->put(route('settings.store.receipt.update'), $this->receiptPayload());
        $this->actingAs($owner)->put(route('settings.store.business.update'), [
            'default_minimum_stock' => '1.000',
            'maximum_cashier_discount' => '10.000',
        ]);

        foreach ([
            'store_settings_updated',
            'receipt_settings_updated',
            'business_settings_updated',
        ] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $owner->id,
                'action' => $action,
                'module' => 'settings',
            ]);
        }
    }
}
