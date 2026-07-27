<?php

namespace Tests\Feature\StoreSetting;

use App\Services\Audit\AuditLogService;
use RuntimeException;

class StoreSettingTransactionTest extends StoreSettingTestCase
{
    public function test_audit_failure_rolls_back_entire_setting_group(): void
    {
        $audit = $this->createMock(AuditLogService::class);
        $audit->method('record')->willThrowException(new RuntimeException('audit failed'));
        $this->app->instance(AuditLogService::class, $audit);
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->put(route('settings.store.general.update'), [
            'store_name' => 'Tidak Boleh Tersimpan',
            'store_address' => 'Alamat rollback',
            'store_phone' => '08123',
        ])->assertSessionHasErrors('settings');

        $this->assertDatabaseMissing('settings', ['key' => 'store.name', 'value' => 'Tidak Boleh Tersimpan']);
        $this->assertDatabaseMissing('settings', ['key' => 'store.address', 'value' => 'Alamat rollback']);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
