<?php

namespace Tests\Feature\AdminDashboard;

class AdminDashboardDataSecurityTest extends AdminDashboardTestCase
{
    public function test_admin_endpoint_rejects_scope_manipulation_and_hides_secrets(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);

        $this->getAdminData($admin, ['cashier_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cashier_id');

        $content = $this->getAdminData($admin)->assertOk()->getContent();
        $this->assertStringNotContainsString('APP_KEY', $content);
        $this->assertStringNotContainsString('DB_PASSWORD', $content);
        $this->assertStringNotContainsString('checkout_token', $content);
        $this->assertStringNotContainsString('stack trace', strtolower($content));
    }
}
