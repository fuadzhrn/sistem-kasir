<?php

namespace Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\User;
use Tests\Feature\OwnerDashboard\OwnerDashboardTestCase;

class DashboardRedirectTest extends OwnerDashboardTestCase
{
    public function test_dashboard_redirects_each_known_role_to_its_workspace(): void
    {
        $branch = $this->createBranch();

        foreach ([
            'owner' => 'dashboard.owner',
            'admin' => 'dashboard.admin',
            'cashier' => 'dashboard.cashier',
        ] as $role => $route) {
            $user = $this->createUser($role, $role === 'owner' ? null : $branch);

            $this->actingAs($user)
                ->get(route('dashboard', ['role' => 'owner']))
                ->assertRedirect(route($route));
        }
    }

    public function test_guest_and_inactive_users_cannot_open_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $inactive = $this->createUser('cashier', $this->createBranch(), [
            'is_active' => false,
        ]);

        $this->actingAs($inactive)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_unknown_role_is_rejected(): void
    {
        $role = Role::query()->create([
            'name' => 'Tidak Dikenal',
            'slug' => 'unknown',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_login_preserves_safe_intended_url_and_rejects_external_redirect(): void
    {
        $owner = $this->createUser('owner', attributes: [
            'username' => 'owner.intended',
            'password' => 'Password123',
        ]);

        $this->withSession(['url.intended' => route('account.index')])
            ->post(route('login.store'), [
                'login_role' => 'owner',
                'login' => $owner->username,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('account.index'));

        auth()->logout();

        $this->withSession(['url.intended' => 'https://evil.example/phishing'])
            ->post(route('login.store'), [
                'login_role' => 'owner',
                'login' => $owner->username,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('dashboard.owner'));
    }
}
