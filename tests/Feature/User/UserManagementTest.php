<?php

namespace Tests\Feature\User;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends UserTestCase
{
    public function test_owner_can_create_each_role_with_normalization_and_hashing(): void
    {
        $owner = $this->createUser('owner');
        $branch = Branch::factory()->create();

        $this->actingAs($owner)
            ->post(route('users.store'), $this->validPayload('owner', $branch, [
                'name' => ' Owner Baru ',
                'username' => ' OWNER.BARU ',
                'email' => ' OWNER.BARU@EXAMPLE.TEST ',
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect();

        $newOwner = User::query()->where('username', 'owner.baru')->firstOrFail();
        $this->assertNull($newOwner->branch_id);
        $this->assertSame('owner.baru@example.test', $newOwner->email);
        $this->assertTrue(Hash::check('PasswordBaru123', $newOwner->password));

        foreach (['admin', 'cashier'] as $role) {
            $this->actingAs($owner)
                ->post(route('users.store'), $this->validPayload($role, $branch, [
                    'username' => "{$role}.baru",
                    'email' => null,
                ]))
                ->assertRedirect();

            $this->assertDatabaseHas('users', [
                'username' => "{$role}.baru",
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);
        }
    }

    public function test_user_creation_rejects_invalid_role_branch_identity_and_password(): void
    {
        $owner = $this->createUser('owner');
        $inactiveBranch = Branch::factory()->create(['is_active' => false]);
        $inactiveRole = Role::factory()->create(['slug' => 'inactive-role', 'is_active' => false]);
        $existing = $this->createUser('admin');

        $this->actingAs($owner)
            ->post(route('users.store'), $this->validPayload('admin', null, ['branch_id' => null]))
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($owner)
            ->post(route('users.store'), $this->validPayload('cashier', $inactiveBranch))
            ->assertSessionHasErrors('branch_id');
        $this->actingAs($owner)
            ->post(route('users.store'), $this->validPayload('owner', null, ['role_id' => $inactiveRole->id]))
            ->assertSessionHasErrors('role_id');
        $this->actingAs($owner)
            ->post(route('users.store'), $this->validPayload('owner', null, [
                'username' => $existing->username,
                'email' => $existing->email,
                'password' => 'lemah',
                'password_confirmation' => 'berbeda',
            ]))
            ->assertSessionHasErrors(['username', 'email', 'password']);
    }

    public function test_owner_can_edit_identity_and_role_without_changing_password(): void
    {
        $owner = $this->createUser('owner');
        $branch = Branch::factory()->create();
        $target = $this->createUser('admin', $branch);
        $passwordHash = $target->password;

        $this->actingAs($owner)
            ->get(route('users.edit', $target))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="is_active"', false);

        $this->actingAs($owner)
            ->put(route('users.update', $target), [
                'name' => ' Pengguna Diperbarui ',
                'username' => ' PENGGUNA.UPDATE ',
                'email' => '',
                'role_id' => $this->createRole('owner')->id,
                'branch_id' => $branch->id,
                'password' => 'TidakBolehDipakai123',
                'is_active' => false,
            ])
            ->assertRedirect(route('users.show', $target));

        $target->refresh();
        $this->assertSame('Pengguna Diperbarui', $target->name);
        $this->assertSame('pengguna.update', $target->username);
        $this->assertNull($target->email);
        $this->assertNull($target->branch_id);
        $this->assertTrue($target->is_active);
        $this->assertSame($passwordHash, $target->password);
    }

    public function test_last_active_owner_cannot_be_changed_to_another_role(): void
    {
        $owner = $this->createUser('owner');
        $branch = Branch::factory()->create();

        $this->actingAs($owner)
            ->put(route('users.update', $owner), [
                'name' => $owner->name,
                'username' => $owner->username,
                'email' => $owner->email,
                'role_id' => $this->createRole('admin')->id,
                'branch_id' => $branch->id,
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertTrue($owner->fresh()->isOwner());
    }

    public function test_owner_cannot_change_own_role_but_can_change_another_owner_when_one_remains(): void
    {
        $actor = $this->createUser('owner', null, ['username' => 'owner.actor']);
        $target = $this->createUser('owner', null, ['username' => 'owner.target']);
        $branch = Branch::factory()->create();
        $adminRole = $this->createRole('admin');

        $this->actingAs($actor)
            ->put(route('users.update', $actor), [
                'name' => $actor->name,
                'username' => $actor->username,
                'email' => $actor->email,
                'role_id' => $adminRole->id,
                'branch_id' => $branch->id,
            ])
            ->assertSessionHasErrors('role_id');

        $this->actingAs($actor)
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'username' => $target->username,
                'email' => $target->email,
                'role_id' => $adminRole->id,
                'branch_id' => $branch->id,
            ])
            ->assertRedirect(route('users.show', $target));

        $this->assertTrue($actor->fresh()->isOwner());
        $this->assertTrue($target->fresh()->isAdmin());
        $this->assertSame($branch->id, $target->fresh()->branch_id);
    }
}
