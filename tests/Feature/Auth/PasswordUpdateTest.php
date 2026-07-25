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

    public function test_guest_cannot_open_change_password_page(): void
    {
        $this->get(route('account.password.edit'))->assertRedirect(route('login'));
    }

    public function test_owner_can_open_self_password_page_without_target_selector(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');

        $this->actingAs($owner)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSeeText('Ubah Kata Sandi Owner')
            ->assertSee('name="current_password"', false)
            ->assertDontSee('name="user_id"', false);
    }

    public function test_admin_and_cashier_cannot_open_or_submit_password_page(): void
    {
        $branch = Branch::factory()->create();
        $admin = $this->createUser('admin', $branch, 'admin.test');
        $cashier = $this->createUser('cashier', $branch, 'cashier.test');
        $payload = [
            'current_password' => 'Password123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ];

        $this->actingAs($admin)->get(route('account.password.edit'))->assertForbidden();
        $this->actingAs($admin)->put(route('account.password.update'), $payload)->assertForbidden();
        $this->actingAs($cashier)->get(route('account.password.edit'))->assertForbidden();
        $this->actingAs($cashier)->put(route('account.password.update'), $payload)->assertForbidden();
    }

    public function test_owner_can_change_own_password_and_session_is_regenerated(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $this->actingAs($owner);
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();

        $this->put(route('account.password.update'), [
            'current_password' => 'OwnerPassword123',
            'password' => 'OwnerPasswordBaru123',
            'password_confirmation' => 'OwnerPasswordBaru123',
        ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('status', __('auth.password_updated'));

        $this->assertTrue(Hash::check('OwnerPasswordBaru123', $owner->fresh()->password));
        $this->assertAuthenticatedAs($owner);
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');
        $oldPassword = $owner->password;

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'current_password' => 'PasswordSalah123',
                'password' => 'OwnerPasswordBaru123',
                'password_confirmation' => 'OwnerPasswordBaru123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($oldPassword, $owner->fresh()->password);
    }

    public function test_password_confirmation_strength_and_difference_are_validated(): void
    {
        $owner = $this->createUser('owner', null, 'owner.test', 'OwnerPassword123');

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'current_password' => 'OwnerPassword123',
                'password' => 'lemah',
                'password_confirmation' => 'berbeda',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($owner)
            ->put(route('account.password.update'), [
                'current_password' => 'OwnerPassword123',
                'password' => 'OwnerPassword123',
                'password_confirmation' => 'OwnerPassword123',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_only_owner_sees_self_password_action(): void
    {
        $branch = Branch::factory()->create();
        $owner = $this->createUser('owner', null, 'owner.test');
        $admin = $this->createUser('admin', $branch, 'admin.test');

        $this->actingAs($owner)
            ->get(route('account.index'))
            ->assertSeeText('Ubah Kata Sandi Saya');

        $this->actingAs($admin)
            ->get(route('account.index'))
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
