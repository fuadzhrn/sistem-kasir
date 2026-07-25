<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_password_management_page(): void
    {
        $this->get(route('account.password.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_open_password_management_page_and_see_all_accounts(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $admin = $this->createUser('admin', Branch::factory()->create(), 'admin.test');
        $cashier = $this->createUser('cashier', Branch::factory()->create(), 'cashier.test');

        $this->actingAs($owner)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSeeText('Kelola Kata Sandi Pengguna')
            ->assertSeeText($owner->username)
            ->assertSeeText($admin->username)
            ->assertSeeText($cashier->username)
            ->assertSee('name="user_id"', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_admin_and_cashier_cannot_open_password_management_page(): void
    {
        $branch = Branch::factory()->create();
        $admin = $this->createUser('admin', $branch, 'admin.test');
        $cashier = $this->createUser('cashier', $branch, 'cashier.test');

        $this->actingAs($admin)
            ->get(route('account.password.edit'))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('account.password.edit'))
            ->assertForbidden();
    }

    public function test_admin_and_cashier_cannot_change_their_own_or_another_password(): void
    {
        $branch = Branch::factory()->create();
        $admin = $this->createUser('admin', $branch, 'admin.test');
        $cashier = $this->createUser('cashier', $branch, 'cashier.test');
        $adminPassword = $admin->password;
        $cashierPassword = $cashier->password;

        $payload = [
            'current_password' => 'Password123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ];

        $this->actingAs($admin)
            ->put(route('account.password.update'), [...$payload, 'user_id' => $admin->id])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('account.password.update'), [...$payload, 'user_id' => $cashier->id])
            ->assertForbidden();

        $this->actingAs($cashier)
            ->put(route('account.password.update'), [...$payload, 'user_id' => $cashier->id])
            ->assertForbidden();

        $this->assertSame($adminPassword, $admin->fresh()->password);
        $this->assertSame($cashierPassword, $cashier->fresh()->password);
    }

    public function test_owner_can_change_admin_and_cashier_passwords(): void
    {
        $branch = Branch::factory()->create();
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $admin = $this->createUser('admin', $branch, 'admin.test');
        $cashier = $this->createUser('cashier', $branch, 'cashier.test');

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'user_id' => $admin->id,
                'current_password' => 'OwnerPassword123',
                'password' => 'AdminPasswordBaru123',
                'password_confirmation' => 'AdminPasswordBaru123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('status', __('auth.password_updated_for', ['name' => $admin->name]));

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'user_id' => $cashier->id,
                'current_password' => 'OwnerPassword123',
                'password' => 'KasirPasswordBaru123',
                'password_confirmation' => 'KasirPasswordBaru123',
            ])
            ->assertRedirect(route('account.password.edit'));

        $this->assertTrue(Hash::check('AdminPasswordBaru123', $admin->fresh()->password));
        $this->assertTrue(Hash::check('KasirPasswordBaru123', $cashier->fresh()->password));
        $this->assertTrue(Hash::check('OwnerPassword123', $owner->fresh()->password));
    }

    public function test_owner_can_change_own_password_and_session_is_regenerated(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $this->actingAs($owner);
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();

        $this->put(route('account.password.update'), [
            'user_id' => $owner->id,
            'current_password' => 'OwnerPassword123',
            'password' => 'OwnerPasswordBaru123',
            'password_confirmation' => 'OwnerPasswordBaru123',
        ])->assertRedirect(route('account.password.edit'));

        $this->assertTrue(Hash::check('OwnerPasswordBaru123', $owner->fresh()->password));
        $this->assertAuthenticatedAs($owner);
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
    }

    public function test_wrong_owner_password_is_rejected(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $admin = $this->createUser('admin', Branch::factory()->create(), 'admin.test');
        $oldPassword = $admin->password;

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'user_id' => $admin->id,
                'current_password' => 'PasswordSalah123',
                'password' => 'AdminPasswordBaru123',
                'password_confirmation' => 'AdminPasswordBaru123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($oldPassword, $admin->fresh()->password);
    }

    public function test_password_confirmation_and_strength_are_validated(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $admin = $this->createUser('admin', Branch::factory()->create(), 'admin.test');

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'user_id' => $admin->id,
                'current_password' => 'OwnerPassword123',
                'password' => 'pendek',
                'password_confirmation' => 'berbeda',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_new_password_must_differ_from_target_current_password(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $admin = $this->createUser('admin', Branch::factory()->create(), 'admin.test', 'AdminPassword123');

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'user_id' => $admin->id,
                'current_password' => 'OwnerPassword123',
                'password' => 'AdminPassword123',
                'password_confirmation' => 'AdminPassword123',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_target_account_must_exist(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'user_id' => 999999,
                'current_password' => 'OwnerPassword123',
                'password' => 'PasswordBaru123',
                'password_confirmation' => 'PasswordBaru123',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_only_owner_sees_password_management_action_on_account_page(): void
    {
        $branch = Branch::factory()->create();
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $admin = $this->createUser('admin', $branch, 'admin.test');
        $cashier = $this->createUser('cashier', $branch, 'cashier.test');

        $this->actingAs($owner)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSeeText('Kelola Kata Sandi');

        $this->actingAs($admin)
            ->get(route('account.index'))
            ->assertOk()
            ->assertDontSee('href="'.route('account.password.edit').'"', false)
            ->assertSeeText('Kata sandi dikelola Owner');

        $this->actingAs($cashier)
            ->get(route('account.index'))
            ->assertOk()
            ->assertDontSee('href="'.route('account.password.edit').'"', false)
            ->assertSeeText('Kata sandi dikelola Owner');
    }

    private function createUser(
        string $roleSlug,
        ?Branch $branch,
        string $username,
        string $password = 'Password123',
    ): User {
        $role = Role::factory()->create([
            'name' => ucfirst($roleSlug),
            'slug' => $roleSlug,
            'is_active' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role,
            'branch_id' => $branch,
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => $password,
            'is_active' => true,
        ]);
    }
}
