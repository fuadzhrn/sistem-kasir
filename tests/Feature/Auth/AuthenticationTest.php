<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Username atau Email')
            ->assertSeeText('Kata Sandi')
            ->assertSee('name="_token"', false)
            ->assertSee('assets/css/pages/auth/login.css', false)
            ->assertSee('assets/js/components/password-toggle.js', false)
            ->assertDontSeeText('Registrasi')
            ->assertDontSeeText('Remember me')
            ->assertDontSeeText('Akun demo');
    }

    public function test_active_user_can_login_with_username_and_last_login_is_updated(): void
    {
        $user = $this->createUser();

        $response = $this->post(route('login.store'), [
            'login' => '  '.Str::upper($user->username).'  ',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_active_user_can_login_with_email(): void
    {
        $user = $this->createUser();

        $response = $this->post(route('login.store'), [
            'login' => '  '.Str::upper((string) $user->email).'  ',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_unknown_user_receive_the_same_error(): void
    {
        $user = $this->createUser();

        $wrongPassword = $this->from(route('login'))->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'Password999',
        ]);
        $wrongPassword->assertRedirect(route('login'));
        $wrongPassword->assertSessionHasErrors([
            'login' => __('auth.failed'),
        ]);

        $unknownUser = $this->from(route('login'))->post(route('login.store'), [
            'login' => 'tidak-dikenal',
            'password' => 'Password999',
        ]);
        $unknownUser->assertRedirect(route('login'));
        $unknownUser->assertSessionHasErrors([
            'login' => __('auth.failed'),
        ]);

        $this->assertGuest();
    }

    public function test_inactive_user_is_rejected_with_generic_credentials_error(): void
    {
        $user = $this->createUser(['is_active' => false]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login' => __('auth.failed'),
            ]);

        $this->assertGuest();
    }

    public function test_failed_login_does_not_flash_password_to_session(): void
    {
        $user = $this->createUser();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'PasswordSangatRahasia123',
            ])
            ->assertSessionHasErrors('login');

        $this->assertSame($user->username, session()->getOldInput('login'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_session_is_regenerated_after_successful_login(): void
    {
        $user = $this->createUser();
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'Password123',
        ])->assertRedirect(route('account.index'));

        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('account.index'));
    }

    public function test_guest_cannot_open_account_and_authenticated_user_can(): void
    {
        $this->get(route('account.index'))->assertRedirect(route('login'));

        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSeeText('Akun Saya')
            ->assertSeeText($user->username)
            ->assertDontSee($user->password)
            ->assertDontSee($user->remember_token ?? 'nilai-tidak-ada');
    }

    public function test_user_can_logout_only_with_post_and_session_is_invalidated(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();
        $previousToken = $this->app['session']->token();
        $this->withSession(['private-marker' => 'rahasia']);

        $this->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('auth.logged_out'));

        $this->assertGuest();
        $this->assertFalse($this->app['session']->has('private-marker'));
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
        $this->assertNotSame($previousToken, $this->app['session']->token());
        $this->get('/logout')->assertStatus(405);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = $this->createUser();
        $key = Str::lower($user->username).'|127.0.0.1';
        RateLimiter::clear($key);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'login' => $user->username,
                'password' => 'Password999',
            ])->assertSessionHasErrors('login');
        }

        $response = $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'Password123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            $response->getSession()->get('errors')->first('login'),
        );
        $this->assertSame(5, RateLimiter::attempts($key));
        $this->assertGuest();
    }

    public function test_rate_limiter_is_cleared_after_successful_login(): void
    {
        $user = $this->createUser();
        $key = Str::lower($user->username).'|127.0.0.1';
        RateLimiter::clear($key);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'Password999',
        ])->assertSessionHasErrors('login');
        $this->assertSame(1, RateLimiter::attempts($key));

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'Password123',
        ])->assertRedirect(route('account.index'));

        $this->assertSame(0, RateLimiter::attempts($key));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => Role::factory(),
            'branch_id' => Branch::factory(),
            'username' => 'kasir.aktif',
            'email' => 'kasir@example.com',
            'password' => 'Password123',
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
