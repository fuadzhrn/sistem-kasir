<?php

namespace Tests\Feature\AdminDashboard;

use App\Models\User;
use Tests\Feature\OwnerDashboard\OwnerDashboardTestCase;

abstract class AdminDashboardTestCase extends OwnerDashboardTestCase
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function getAdminData(User $admin, array $parameters = [])
    {
        return $this->actingAs($admin)
            ->getJson(route('dashboard.admin.data', [
                'period' => 'this_month',
                ...$parameters,
            ]));
    }
}
