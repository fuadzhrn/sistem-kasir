<?php

namespace Tests\Feature\Activity;

class ActivityLogDataSecurityTest extends ActivityLogTestCase
{
    public function test_admin_response_hides_internal_cost_ip_and_sensitive_metadata(): void
    {
        $branch = $this->branch();
        $admin = $this->user('admin', $branch);
        $log = $this->log($admin, $branch, [
            'metadata' => [
                'selling_price' => '20000.00',
                'purchase_price' => '10000.00',
                'nested' => ['average_cost' => '9000.00'],
            ],
            'ip_address' => '10.20.30.40',
        ]);

        $this->actingAs($admin)
            ->get(route('activities.show', $log))
            ->assertOk()
            ->assertSee('Rp20.000')
            ->assertDontSee('20000.00')
            ->assertDontSee('10000.00')
            ->assertDontSee('9000.00')
            ->assertDontSee('10.20.30.40')
            ->assertSee('hanya dapat dilihat oleh Owner');
    }
}
