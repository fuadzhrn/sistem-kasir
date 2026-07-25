<?php

namespace Tests\Feature\Authorization;

use App\Services\Authorization\BranchAccessService;

class AuthorizationUrlManipulationTest extends AuthorizationTestCase
{
    public function test_branch_id_from_query_or_hidden_input_cannot_expand_admin_access(): void
    {
        $branchA = $this->createBranch('URA', 'Cabang A');
        $branchB = $this->createBranch('URB', 'Cabang B');
        $admin = $this->createUser('admin', $branchA);
        $service = app(BranchAccessService::class);

        $this->assertSame($branchA->id, $service->resolveBranchId($admin, $branchB->id));

        $this->actingAs($admin)
            ->get(route('authorization-check.branch', $branchA, false).'?branch_id='.$branchB->id.'&role=owner')
            ->assertOk()
            ->assertSeeText('Cabang A')
            ->assertDontSeeText('Cabang B');

        $this->actingAs($admin)
            ->get(route('authorization-check.branch', $branchB, false).'?branch_id='.$branchA->id)
            ->assertNotFound();
    }

    public function test_direct_owner_url_is_forbidden_for_admin_and_cashier(): void
    {
        $branch = $this->createBranch('URO');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($admin)->get(route('authorization-check.owner'))->assertForbidden();
        $this->actingAs($cashier)->get(route('authorization-check.owner'))->assertForbidden();
    }

    public function test_403_page_is_safe_and_guest_still_redirects_to_login(): void
    {
        $cashier = $this->createUser('cashier', $this->createBranch('403'));

        $this->actingAs($cashier)
            ->get(route('authorization-check.owner'))
            ->assertForbidden()
            ->assertSeeText('Akses Ditolak')
            ->assertSeeText('Ke Akun Saya')
            ->assertDontSeeText('EnsureUserHasRole')
            ->assertDontSeeText('BranchPolicy')
            ->assertDontSeeText('role:owner')
            ->assertDontSeeText('Stack trace');

        auth()->logout();

        $this->get(route('authorization-check.owner'))
            ->assertRedirect(route('login'));
    }

    public function test_authorization_check_returns_not_found_outside_local_or_testing(): void
    {
        $owner = $this->createUser('owner');
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->actingAs($owner)
            ->get(route('authorization-check.index'))
            ->assertNotFound();
    }

    public function test_owner_must_still_be_authenticated_and_active(): void
    {
        $branch = $this->createBranch('ACT');
        $owner = $this->createUser('owner', attributes: ['is_active' => false]);

        $this->actingAs($owner)
            ->get(route('authorization-check.branch', $branch))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
