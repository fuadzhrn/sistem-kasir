<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_available_and_uses_csrf(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSeeText('Lupa kata sandi?')
            ->assertSee('name="_token"', false)
            ->assertSee('assets/css/pages/auth/forgot-password.css', false);
    }

    public function test_active_account_receives_reset_notification_with_generic_response(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_and_inactive_accounts_receive_generic_response_without_notification(): void
    {
        Notification::fake();
        $inactiveUser = $this->createUser(['is_active' => false]);

        $this->post(route('password.email'), ['email' => $inactiveUser->email])
            ->assertSessionHas('status', __('passwords.sent'));
        $this->post(route('password.email'), ['email' => 'tidak-ada@example.com'])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertNothingSent();
    }

    public function test_valid_token_resets_active_user_password_and_cannot_be_reused(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);
        $storedToken = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->value('token');

        $this->assertNotSame($token, $storedToken);
        $this->assertTrue(Hash::check($token, $storedToken));

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        $this->assertTrue(Hash::check('PasswordBaru123', $user->fresh()->password));
        $this->assertNotSame('PasswordBaru123', $user->fresh()->password);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordLain123',
            'password_confirmation' => 'PasswordLain123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('PasswordBaru123', $user->fresh()->password));
    }

    public function test_invalid_token_is_rejected_without_changing_password(): void
    {
        $user = $this->createUser();
        $oldPassword = $user->password;

        $this->post(route('password.update'), [
            'token' => 'token-tidak-valid',
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertSessionHasErrors([
            'email' => __('passwords.reset_failed'),
        ]);

        $this->assertSame($oldPassword, $user->fresh()->password);
        $this->assertNull(session()->getOldInput('token'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_inactive_account_cannot_use_an_existing_reset_token(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);
        $oldPassword = $user->password;
        $user->update(['is_active' => false]);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertSessionHasErrors([
            'email' => __('passwords.reset_failed'),
        ]);

        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_password_confirmation_and_strength_are_validated(): void
    {
        $user = $this->createUser();

        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'lemah',
            'password_confirmation' => 'berbeda',
        ])
            ->assertSessionHasErrors('password')
            ->assertSessionDoesntHaveErrors('token');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => Role::factory(),
            'branch_id' => Branch::factory(),
            'username' => 'pemilik.toko',
            'email' => 'pemilik@example.com',
            'password' => 'Password123',
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
