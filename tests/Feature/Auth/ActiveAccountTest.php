<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActiveAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_access_protected_route(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk();
    }

    public function test_user_is_logged_out_on_next_request_after_account_is_disabled(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();
        $previousToken = $this->app['session']->token();
        $this->withSession(['private-marker' => 'rahasia']);
        $user->update(['is_active' => false]);

        $this->get(route('account.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login' => __('auth.inactive'),
            ]);

        $this->assertGuest();
        $this->assertFalse($this->app['session']->has('private-marker'));
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
        $this->assertNotSame($previousToken, $this->app['session']->token());
    }

    public function test_home_redirects_based_only_on_authentication_state(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));

        $this->actingAs($this->createUser())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_authentication_routes_have_expected_middleware(): void
    {
        $guestRoutes = ['login', 'login.store', 'password.request', 'password.email', 'password.reset', 'password.update'];
        $protectedRoutes = ['logout', 'account.index', 'account.password.edit', 'account.password.update'];

        foreach ($guestRoutes as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];

            $this->assertContains('web', $middleware, "Route {$routeName} tidak memakai middleware web.");
            $this->assertContains('guest', $middleware, "Route {$routeName} tidak memakai middleware guest.");
            $this->assertNotContains('auth', $middleware, "Route {$routeName} tidak boleh memakai middleware auth.");
        }

        foreach ($protectedRoutes as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];

            $this->assertContains('web', $middleware, "Route {$routeName} tidak memakai middleware web.");
            $this->assertContains('auth', $middleware, "Route {$routeName} tidak memakai middleware auth.");
            $this->assertContains('active.user', $middleware, "Route {$routeName} tidak memakai middleware active.user.");
        }
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::factory(),
            'branch_id' => Branch::factory(),
            'username' => 'pengguna.aktif',
            'email' => 'pengguna@example.com',
            'password' => 'Password123',
            'is_active' => true,
        ]);
    }
}
