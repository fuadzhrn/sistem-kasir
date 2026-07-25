<?php

namespace Tests\Feature\User;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;

class UserStatusTest extends UserTestCase
{
    public function test_owner_cannot_deactivate_self_or_last_active_owner(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)
            ->patch(route('users.status.update', $owner), ['is_active' => false])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_owner_can_deactivate_and_reactivate_another_user(): void
    {
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier');

        $this->actingAs($owner)
            ->patch(route('users.status.update', $cashier), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($cashier->fresh()->is_active);

        $this->actingAs($owner)
            ->patch(route('users.status.update', $cashier), ['is_active' => true])
            ->assertRedirect();
        $this->assertTrue($cashier->fresh()->is_active);
    }

    public function test_user_with_inactive_branch_cannot_be_activated(): void
    {
        $owner = $this->createUser('owner');
        $branch = Branch::factory()->create(['is_active' => false]);
        $admin = $this->createUser('admin', $branch, ['is_active' => false]);

        $this->actingAs($owner)
            ->patch(route('users.status.update', $admin), ['is_active' => true])
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($admin->fresh()->is_active);
    }

    public function test_user_with_inactive_role_cannot_be_activated(): void
    {
        $owner = $this->createUser('owner');
        $inactiveRole = Role::factory()->create(['slug' => 'role-nonaktif', 'is_active' => false]);
        $target = User::factory()->create([
            'role_id' => $inactiveRole,
            'branch_id' => Branch::factory(),
            'is_active' => false,
        ]);

        $this->actingAs($owner)
            ->patch(route('users.status.update', $target), ['is_active' => true])
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_inactive_user_is_rejected_at_login_and_next_authenticated_request(): void
    {
        $cashier = $this->createUser('cashier', null, [
            'username' => 'kasir.nonaktif',
            'password' => 'Password123',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'login' => $cashier->username,
            'password' => 'Password123',
        ])->assertSessionHasErrors('login');

        $cashier->update(['is_active' => true]);
        $this->actingAs($cashier);
        $cashier->update(['is_active' => false]);

        $this->get(route('account.index'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
