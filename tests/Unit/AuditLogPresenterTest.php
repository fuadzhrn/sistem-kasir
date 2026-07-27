<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditActionRegistry;
use App\Services\Audit\AuditLogPresenter;
use Tests\TestCase;

class AuditLogPresenterTest extends TestCase
{
    public function test_admin_cannot_receive_internal_financial_metadata(): void
    {
        $admin = new User(['name' => 'Admin']);
        $admin->setRelation('role', new Role(['slug' => 'admin']));
        $log = new ActivityLog([
            'action' => 'product_prices_changed',
            'module' => 'prices',
            'metadata' => [
                'selling_price' => '20000.00',
                'purchase_price' => '10000.00',
                'quantity_before' => '1000.500',
                'quantity_change' => '1.250',
                'quantity_after' => '1001.750',
                'nested' => ['average_cost' => '8000.00', 'safe' => 'ok'],
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $presented = (new AuditLogPresenter(new AuditActionRegistry))->presentForUser($log, $admin);

        $this->assertSame('20000.00', $presented['metadata']['selling_price']);
        $this->assertSame('1.000,5', $presented['metadata']['quantity_before']);
        $this->assertSame('1,25', $presented['metadata']['quantity_change']);
        $this->assertSame('1.001,75', $presented['metadata']['quantity_after']);
        $this->assertArrayNotHasKey('purchase_price', $presented['metadata']);
        $this->assertArrayNotHasKey('average_cost', $presented['metadata']['nested']);
        $this->assertSame('ok', $presented['metadata']['nested']['safe']);
        $this->assertNull($presented['ip_address']);
    }
}
