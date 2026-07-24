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

    public function test_authenticated_user_can_open_change_password_page(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSeeText('Kata Sandi Saat Ini')
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('assets/js/components/password-toggle.js', false);
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();

        $this->put(route('account.password.update'), [
            'current_password' => 'Password123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('status', __('auth.password_updated'));

        $this->assertTrue(Hash::check('PasswordBaru123', $user->fresh()->password));
        $this->assertNotSame('PasswordBaru123', $user->fresh()->password);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->createUser();
        $oldPassword = $user->password;

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'Password999',
                'password' => 'PasswordBaru123',
                'password_confirmation' => 'PasswordBaru123',
            ])
            ->assertSessionHasErrors([
                'current_password' => __('validation.current_password'),
            ]);

        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_different_confirmation_is_rejected(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'Password123',
                'password' => 'PasswordBaru123',
                'password_confirmation' => 'PasswordBerbeda123',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'Password123',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])
            ->assertSessionHasErrors('password');
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::factory(),
            'branch_id' => Branch::factory(),
            'username' => 'admin.cabang',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'is_active' => true,
        ]);
    }
}
