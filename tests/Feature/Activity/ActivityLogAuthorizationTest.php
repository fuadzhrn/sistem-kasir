<?php

namespace Tests\Feature\Activity;

class ActivityLogAuthorizationTest extends ActivityLogTestCase
{
    public function test_guest_is_redirected_and_cashier_is_forbidden(): void
    {
        $branch = $this->branch();
        $cashier = $this->user('cashier', $branch);

        $this->get(route('activities.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('activities.index'))->assertForbidden();
    }

    public function test_no_write_routes_are_exposed(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn (string $name): bool => str_starts_with($name, 'activities.'))
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing(['activities.index', 'activities.show'], $routes);
    }
}
