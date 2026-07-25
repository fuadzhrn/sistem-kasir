<?php

namespace Tests\Feature\Authorization;

class BranchAccessMiddlewareTest extends AuthorizationTestCase
{
    public function test_owner_can_open_every_branch(): void
    {
        $branchA = $this->createBranch('BRA', 'Cabang A');
        $branchB = $this->createBranch('BRB', 'Cabang B');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->get(route('authorization-check.branch', $branchA))->assertOk();
        $this->actingAs($owner)->get(route('authorization-check.branch', $branchB))->assertOk();
    }

    public function test_admins_can_only_open_their_own_branch(): void
    {
        $branchA = $this->createBranch('ADA', 'Cabang A');
        $branchB = $this->createBranch('ADB', 'Cabang B');
        $adminA = $this->createUser('admin', $branchA);
        $adminB = $this->createUser('admin', $branchB);

        $this->actingAs($adminA)->get(route('authorization-check.branch', $branchA))->assertOk();
        $this->actingAs($adminA)->get(route('authorization-check.branch', $branchB))->assertNotFound();
        $this->actingAs($adminB)->get(route('authorization-check.branch', $branchB))->assertOk();
        $this->actingAs($adminB)->get(route('authorization-check.branch', $branchA))->assertNotFound();
    }

    public function test_cashiers_can_only_open_their_own_branch(): void
    {
        $branchA = $this->createBranch('KSA', 'Cabang A');
        $branchB = $this->createBranch('KSB', 'Cabang B');
        $cashierA = $this->createUser('cashier', $branchA);
        $cashierB = $this->createUser('cashier', $branchB);

        $this->actingAs($cashierA)->get(route('authorization-check.branch', $branchA))->assertOk();
        $this->actingAs($cashierA)->get(route('authorization-check.branch', $branchB))->assertNotFound();
        $this->actingAs($cashierB)->get(route('authorization-check.branch', $branchB))->assertOk();
        $this->actingAs($cashierB)->get(route('authorization-check.branch', $branchA))->assertNotFound();
    }

    public function test_non_owner_without_branch_is_rejected(): void
    {
        $branch = $this->createBranch('NUL');
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('authorization-check.branch', $branch))
            ->assertNotFound();
    }

    public function test_query_parameter_cannot_replace_bound_branch(): void
    {
        $branchA = $this->createBranch('QPA', 'Cabang A');
        $branchB = $this->createBranch('QPB', 'Cabang B');
        $admin = $this->createUser('admin', $branchA);

        $this->actingAs($admin)
            ->get(route('authorization-check.branch', $branchA).'?branch_id='.$branchB->id)
            ->assertOk()
            ->assertSeeText('Cabang A')
            ->assertDontSeeText('Cabang B');
    }
}
