<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Authorization\AuthorizationTestCase;

class LoginRoleSelectionTest extends AuthorizationTestCase
{
    public function test_login_page_has_accessible_role_choices_and_no_demo_credentials(): void
    {
        $response = $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Selamat Datang')
            ->assertSeeText('Pilih jenis akun Anda untuk masuk')
            ->assertSeeText('Owner')
            ->assertSeeText('Admin Cabang')
            ->assertSeeText('Kasir')
            ->assertSeeText('Ingat saya')
            ->assertSee('type="radio"', false)
            ->assertSee('name="login_role"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-login-form', false)
            ->assertSee('assets/css/pages/auth/login.css', false)
            ->assertSee('assets/js/pages/auth/login.js', false);

        $content = $response->getContent();

        $this->assertStringNotContainsString('Password123', $content);
        $this->assertStringNotContainsString('owner.test', $content);
        $this->assertStringNotContainsString('admin.test', $content);
        $this->assertStringNotContainsString('cashier.test', $content);
        $this->assertStringNotContainsString('onclick=', $content);
    }

    public function test_login_requires_role_login_and_password_with_indonesian_messages(): void
    {
        $response = $this->from(route('login'))
            ->post(route('login.store'), []);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login_role' => 'Silakan pilih Owner, Admin Cabang, atau Kasir terlebih dahulu.',
                'login' => 'Silakan masukkan username atau email.',
                'password' => 'Silakan masukkan password.',
            ]);
        $this->assertGuest();
    }

    public function test_manipulated_role_value_is_rejected_without_changing_user_role(): void
    {
        $user = $this->loginUser('cashier');
        $originalRoleId = $user->role_id;

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login_role' => 'super-admin',
                'login' => $user->username,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login_role' => 'Jenis akun yang dipilih tidak tersedia.',
            ]);

        $this->assertGuest();
        $this->assertSame($originalRoleId, $user->fresh()->role_id);
    }

    #[DataProvider('matchingRoleCases')]
    public function test_matching_role_logs_in_and_redirects_to_actual_role_dashboard(
        string $role,
        string $dashboardRoute,
    ): void {
        $user = $this->loginUser($role);

        $this->post(route('login.store'), [
            'login_role' => $role,
            'login' => $user->username,
            'password' => 'Password123',
        ])
            ->assertRedirect(route($dashboardRoute));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function matchingRoleCases(): array
    {
        return [
            'owner' => ['owner', 'dashboard.owner'],
            'admin cabang' => ['admin', 'dashboard.admin'],
            'kasir' => ['cashier', 'dashboard.cashier'],
        ];
    }

    #[DataProvider('mismatchedRoleCases')]
    public function test_valid_credentials_with_mismatched_selected_role_are_rejected(
        string $actualRole,
        string $selectedRole,
    ): void {
        $user = $this->loginUser($actualRole);
        $this->app['session']->start();
        $previousSessionId = $this->app['session']->getId();

        $response = $this->from(route('login'))
            ->post(route('login.store'), [
                'login_role' => $selectedRole,
                'login' => $user->username,
                'password' => 'Password123',
            ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login_role' => __('auth.role_mismatch'),
            ]);

        $this->assertGuest();
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
        $this->assertSame($actualRole, $user->fresh()->role->slug);
        $this->assertSame($selectedRole, session()->getOldInput('login_role'));
        $this->assertSame($user->username, session()->getOldInput('login'));
        $this->assertNull(session()->getOldInput('password'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function mismatchedRoleCases(): array
    {
        return [
            'owner memilih Kasir' => ['owner', 'cashier'],
            'admin memilih Owner' => ['admin', 'owner'],
            'kasir memilih Admin' => ['cashier', 'admin'],
        ];
    }

    public function test_inactive_account_receives_specific_message_and_remains_logged_out(): void
    {
        $user = $this->loginUser('cashier', ['is_active' => false]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login_role' => 'cashier',
                'login' => $user->username,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login' => __('auth.inactive'),
            ]);

        $this->assertGuest();
    }

    public function test_remember_me_creates_recaller_cookie(): void
    {
        $user = $this->loginUser('cashier');
        $recallerName = Auth::guard('web')->getRecallerName();

        $this->post(route('login.store'), [
            'login_role' => 'cashier',
            'login' => $user->username,
            'password' => 'Password123',
            'remember' => '1',
        ])
            ->assertRedirect(route('dashboard.cashier'))
            ->assertCookie($recallerName);

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_is_never_flashed_or_rendered_after_failed_login(): void
    {
        $user = $this->loginUser('cashier');
        $secret = 'Password-Tidak-Boleh-Muncul-123';

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login_role' => 'cashier',
                'login' => $user->username,
                'password' => $secret,
            ])
            ->assertSessionHasErrors('login');

        $this->assertNull(session()->getOldInput('password'));
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee($secret);
    }

    public function test_unauthorized_intended_route_falls_back_to_actual_role_dashboard(): void
    {
        $cashier = $this->loginUser('cashier');

        $this->withSession(['url.intended' => route('dashboard.owner')])
            ->post(route('login.store'), [
                'login_role' => 'cashier',
                'login' => $cashier->username,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('dashboard.cashier'));

        $this->assertAuthenticatedAs($cashier);
        $this->get(route('dashboard.owner'))->assertForbidden();
    }

    public function test_frontend_contract_uses_modular_css_and_vanilla_javascript(): void
    {
        $blade = file_get_contents(resource_path('views/auth/login.blade.php'));
        $css = file_get_contents(public_path('assets/css/pages/auth/login.css'));
        $javascript = file_get_contents(public_path('assets/js/pages/auth/login.js'));

        $this->assertStringContainsString('auth-role-selector', $blade);
        $this->assertStringContainsString('auth-role-card__selected', $blade);
        $this->assertStringContainsString('aria-live="polite"', $blade);
        $this->assertStringContainsString('type="button"', $blade);
        $this->assertStringNotContainsString('onclick=', $blade);
        $this->assertStringNotContainsString('<style', $blade);
        $this->assertStringNotContainsString('<script>', $blade);

        foreach (['1280px', '1024px', '768px', '480px'] as $breakpoint) {
            $this->assertStringContainsString('@media (max-width: '.$breakpoint.')', $css);
        }

        $this->assertStringContainsString('updateRoleState', $javascript);
        $this->assertStringContainsString('isSubmitting', $javascript);
        $this->assertStringContainsString('Memeriksa akun...', $javascript);
        $this->assertStringContainsString('data-loading', $javascript);
        $this->assertStringNotContainsString('jQuery', $javascript);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function loginUser(string $role, array $attributes = []): User
    {
        $branch = $role === 'owner' ? null : $this->createBranch(
            'LOGIN-'.mb_strtoupper($role).'-'.fake()->unique()->numberBetween(100, 999),
        );

        return $this->createUser($role, $branch, [
            'username' => $role.'.login.'.fake()->unique()->numberBetween(100, 999),
            'email' => $role.'.login.'.fake()->unique()->numberBetween(100, 999).'@example.com',
            'password' => 'Password123',
            ...$attributes,
        ]);
    }
}
