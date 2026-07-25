<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\Gate;

class AuthorizationGateTest extends AuthorizationTestCase
{
    public function test_profit_gate_respects_role_and_branch(): void
    {
        $branchA = $this->createBranch('PFA');
        $branchB = $this->createBranch('PFB');
        $owner = $this->createUser('owner');
        $adminA = $this->createUser('admin', $branchA);
        $adminB = $this->createUser('admin', $branchB);
        $cashier = $this->createUser('cashier', $branchA);

        $this->assertTrue(Gate::forUser($owner)->allows('view-profit', $branchA));
        $this->assertTrue(Gate::forUser($owner)->allows('view-profit', $branchB));
        $this->assertTrue(Gate::forUser($adminA)->allows('view-profit', $branchA));
        $this->assertFalse(Gate::forUser($adminA)->allows('view-profit', $branchB));
        $this->assertTrue(Gate::forUser($adminB)->allows('view-profit', $branchB));
        $this->assertFalse(Gate::forUser($cashier)->allows('view-profit', $branchA));
        $this->assertFalse(Gate::forUser($cashier)->allows('view-profit', $branchB));
    }

    public function test_global_and_management_gates_are_restricted(): void
    {
        $branch = $this->createBranch('GAT');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        foreach (['view-global-report', 'manage-branches', 'manage-users', 'manage-settings'] as $ability) {
            $this->assertTrue(Gate::forUser($owner)->allows($ability));
            $this->assertFalse(Gate::forUser($admin)->allows($ability));
            $this->assertFalse(Gate::forUser($cashier)->allows($ability));
        }

        $this->assertTrue(Gate::forUser($owner)->allows('view-activity-logs'));
        $this->assertTrue(Gate::forUser($admin)->allows('view-activity-logs'));
        $this->assertFalse(Gate::forUser($cashier)->allows('view-activity-logs'));
    }

    public function test_profit_route_returns_safe_403_when_gate_denies_access(): void
    {
        $branchA = $this->createBranch('PRA', 'Cabang A');
        $branchB = $this->createBranch('PRB', 'Cabang B');
        $admin = $this->createUser('admin', $branchA);
        $cashier = $this->createUser('cashier', $branchA);

        $this->actingAs($admin)->get(route('authorization-check.profit', $branchA))->assertOk();
        $this->actingAs($admin)->get(route('authorization-check.profit', $branchB))->assertForbidden();
        $this->actingAs($cashier)
            ->get(route('authorization-check.profit', $branchA))
            ->assertForbidden()
            ->assertSeeText('Akses Ditolak')
            ->assertDontSeeText('view-profit');
    }
}
