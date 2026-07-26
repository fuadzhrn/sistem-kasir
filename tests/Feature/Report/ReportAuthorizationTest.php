<?php

namespace Tests\Feature\Report;

class ReportAuthorizationTest extends ReportTestCase
{
    public function test_guest_is_redirected_and_cashier_is_forbidden(): void
    {
        $branch = $this->createBranch('RAU');
        $cashier = $this->createUser('cashier', $branch);

        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
        $this->getReport($cashier, 'sales')->assertForbidden();
    }

    public function test_inactive_user_is_rejected(): void
    {
        $owner = $this->createUser('owner', null, ['is_active' => false]);

        $this->actingAs($owner)->get(route('reports.index'))->assertRedirect(route('login'));
    }
}
