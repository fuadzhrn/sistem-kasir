<?php

namespace Tests\Feature\OwnerDashboard;

class OwnerDashboardAuthorizationTest extends OwnerDashboardTestCase
{
    public function test_guest_is_redirected_and_non_owner_roles_are_forbidden(): void
    {
        $branch = $this->createBranch();
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->get(route('dashboard.owner'))->assertRedirect(route('login'));
        $this->getJson(route('dashboard.owner.data'))->assertUnauthorized();

        foreach ([$admin, $cashier] as $user) {
            $this->actingAs($user)->get(route('dashboard.owner'))->assertForbidden();
            $this->actingAs($user)->getJson(route('dashboard.owner.data'))->assertForbidden();
        }
    }

    public function test_inactive_owner_is_rejected_by_active_user_middleware(): void
    {
        $owner = $this->createUser('owner', null, ['is_active' => false]);

        $this->actingAs($owner)->get(route('dashboard.owner'))->assertRedirect(route('login'));
        $this->actingAs($owner)->getJson(route('dashboard.owner.data'))->assertForbidden();
    }
}
