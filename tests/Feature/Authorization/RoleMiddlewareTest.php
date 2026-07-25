<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class RoleMiddlewareTest extends AuthorizationTestCase
{
    public function test_guest_is_redirected_from_role_route(): void
    {
        $this->get(route('authorization-check.owner'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_open_all_role_check_routes(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->get(route('authorization-check.owner'))->assertOk();
        $this->actingAs($owner)->get(route('authorization-check.management'))->assertOk();
        $this->actingAs($owner)->get(route('authorization-check.cashier'))->assertOk();
    }

    public function test_admin_can_open_management_but_not_owner_route(): void
    {
        $admin = $this->createUser('admin', $this->createBranch('ADM'));

        $this->actingAs($admin)
            ->get(route('authorization-check.management'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('authorization-check.owner'))
            ->assertForbidden()
            ->assertSeeText('Akses Ditolak');
    }

    public function test_cashier_can_only_open_all_roles_check_route(): void
    {
        $cashier = $this->createUser('cashier', $this->createBranch('KSR'));

        $this->actingAs($cashier)->get(route('authorization-check.cashier'))->assertOk();
        $this->actingAs($cashier)->get(route('authorization-check.management'))->assertForbidden();
        $this->actingAs($cashier)->get(route('authorization-check.owner'))->assertForbidden();
    }

    public function test_inactive_account_is_rejected_before_role_check(): void
    {
        $owner = $this->createUser('owner', attributes: ['is_active' => false]);

        $this->actingAs($owner)
            ->get(route('authorization-check.owner'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_role_middleware_rejects_unknown_slug_and_ignores_request_role(): void
    {
        Route::middleware(['web', 'auth', 'active.user', 'role:role-tidak-ada'])
            ->get('/test-only/unknown-role', fn (): string => 'tidak boleh tampil');

        $admin = $this->createUser('admin', $this->createBranch('RQA'));

        $this->actingAs($admin)
            ->get('/test-only/unknown-role?role=owner')
            ->assertForbidden()
            ->assertDontSeeText('tidak boleh tampil');
        $this->actingAs($admin)
            ->get(route('authorization-check.owner', ['role' => 'owner']))
            ->assertForbidden();
    }

    public function test_user_role_helpers_use_database_relationship_safely(): void
    {
        $owner = $this->createUser('owner');
        $withoutRole = new User;

        $this->assertTrue($owner->hasRole('owner'));
        $this->assertTrue($owner->hasAnyRole(['admin', 'owner']));
        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isAdmin());
        $this->assertFalse($withoutRole->hasRole('owner'));
    }
}
