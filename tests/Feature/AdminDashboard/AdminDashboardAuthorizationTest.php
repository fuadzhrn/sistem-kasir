<?php

namespace Tests\Feature\AdminDashboard;

class AdminDashboardAuthorizationTest extends AdminDashboardTestCase
{
    public function test_only_active_admin_with_active_branch_can_access_admin_dashboard(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);

        $this->get(route('dashboard.admin'))->assertRedirect(route('login'));
        $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk();
        $this->getAdminData($admin)->assertOk();

        foreach (['owner', 'cashier'] as $role) {
            $user = $this->createUser($role, $role === 'owner' ? null : $branch);
            $this->actingAs($user)->get(route('dashboard.admin'))->assertForbidden();
        }

    }

    public function test_admin_without_operational_branch_is_rejected(): void
    {
        $withoutBranch = $this->createUser('admin');
        $inactiveBranch = $this->createBranch('OFF', ['is_active' => false]);
        $inactiveBranchAdmin = $this->createUser('admin', $inactiveBranch);

        $this->actingAs($withoutBranch)->get(route('dashboard.admin'))->assertForbidden();
        $this->actingAs($inactiveBranchAdmin)->get(route('dashboard.admin'))->assertForbidden();
    }

    public function test_admin_cannot_open_owner_dashboard(): void
    {
        $admin = $this->createUser('admin', $this->createBranch());

        $this->actingAs($admin)->get(route('dashboard.owner'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('dashboard.owner.data'))->assertForbidden();
    }
}
