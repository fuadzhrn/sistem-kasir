<?php

namespace Tests\Feature\CashierDashboard;

class CashierDashboardAuthorizationTest extends CashierDashboardTestCase
{
    public function test_only_active_cashier_with_active_branch_can_access(): void
    {
        $branch = $this->createBranch();
        $cashier = $this->createUser('cashier', $branch);

        $this->get(route('dashboard.cashier'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('dashboard.cashier'))->assertOk();

        foreach (['owner', 'admin'] as $role) {
            $user = $this->createUser($role, $role === 'owner' ? null : $branch);
            $this->actingAs($user)->get(route('dashboard.cashier'))->assertForbidden();
        }
    }

    public function test_cashier_without_operational_branch_is_rejected(): void
    {
        $withoutBranch = $this->createUser('cashier');
        $inactiveBranch = $this->createBranch('OFF', ['is_active' => false]);
        $inactiveBranchCashier = $this->createUser('cashier', $inactiveBranch);

        $this->actingAs($withoutBranch)->get(route('dashboard.cashier'))->assertForbidden();
        $this->actingAs($inactiveBranchCashier)->get(route('dashboard.cashier'))->assertForbidden();
    }
}
