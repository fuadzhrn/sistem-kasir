<?php

namespace Tests\Feature\Cashier;

use Illuminate\Support\Facades\Route;

class CashierAuthorizationTest extends CashierTestCase
{
    public function test_guest_is_redirected_to_login_and_inactive_user_is_logged_out(): void
    {
        $this->get(route('cashier.index'))->assertRedirect(route('login'));

        $branch = $this->createBranch('OFF');
        $cashier = $this->createUser('cashier', $branch, ['is_active' => false]);
        $this->actingAs($cashier)->get(route('cashier.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
    }

    public function test_admin_and_cashier_without_branch_are_forbidden(): void
    {
        $admin = $this->createUser('admin');
        $cashier = $this->createUser('cashier');

        $this->actingAs($admin)->get(route('cashier.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('cashier.index'))->assertForbidden();
    }

    public function test_user_with_inactive_branch_is_forbidden(): void
    {
        $branch = $this->createBranch('INA', ['is_active' => false]);
        $admin = $this->createUser('admin', $branch);

        $this->actingAs($admin)->get(route('cashier.index'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('cashier.products.index'))->assertForbidden();
    }

    public function test_routes_have_required_middleware_and_only_get_cashier_routes_exist(): void
    {
        foreach (['cashier.index', 'cashier.products.index'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $middleware = $route?->gatherMiddleware() ?? [];
            $this->assertContains('web', $middleware);
            $this->assertContains('auth', $middleware);
            $this->assertContains('active.user', $middleware);
            $this->assertContains('role:owner,admin,cashier', $middleware);
            $this->assertSame(['GET', 'HEAD'], $route?->methods());
        }

        $this->assertContains('throttle:90,1', Route::getRoutes()
            ->getByName('cashier.products.index')?->gatherMiddleware() ?? []);
        $this->assertNull(Route::getRoutes()->getByName('cashier.store'));
        $this->assertNull(Route::getRoutes()->getByName('sales.store'));
    }

    public function test_sidebar_links_all_three_roles_to_cashier(): void
    {
        $branch = $this->createBranch('MENU');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($owner)->get(route('account.index'))
            ->assertSee('href="'.route('cashier.index').'"', false);
        $this->actingAs($admin)->get(route('account.index'))
            ->assertSee('href="'.route('cashier.index').'"', false);
        $this->actingAs($cashier)->get(route('account.index'))
            ->assertSee('href="'.route('cashier.index').'"', false);
    }
}
