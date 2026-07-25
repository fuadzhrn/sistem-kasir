<?php

namespace Tests\Feature\Sale;

class SaleAuthorizationTest extends SaleTestCase
{
    public function test_guest_is_rejected_with_json_unauthorized_response(): void
    {
        $this->postJson(route('cashier.checkout.store'), [])
            ->assertUnauthorized();
    }

    public function test_inactive_user_is_logged_out_and_rejected(): void
    {
        $branch = $this->createBranch('OFF');
        $cashier = $this->createUser('cashier', $branch, ['is_active' => false]);

        $this->actingAs($cashier)
            ->postJson(route('cashier.checkout.store'), [])
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');
        $this->assertGuest();
    }

    public function test_owner_must_choose_active_branch(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->postJson(route('cashier.checkout.store'), [
                'checkout_token' => $this->nextToken(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'BRANCH_INACTIVE')
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_role_outside_owner_admin_cashier_is_forbidden_by_middleware(): void
    {
        $branch = $this->createBranch('ROLE');
        $user = $this->createUser('auditor', $branch);

        $this->actingAs($user)
            ->postJson(route('cashier.checkout.store'), [])
            ->assertForbidden();
    }
}
