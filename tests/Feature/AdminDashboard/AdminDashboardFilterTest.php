<?php

namespace Tests\Feature\AdminDashboard;

class AdminDashboardFilterTest extends AdminDashboardTestCase
{
    public function test_default_and_supported_period_filters_work(): void
    {
        $admin = $this->createUser('admin', $this->createBranch());

        $this->actingAs($admin)
            ->getJson(route('dashboard.admin.data'))
            ->assertOk()
            ->assertJsonPath('data.filters.period', 'this_month');

        foreach (['today', 'this_week', 'this_month', 'this_year'] as $period) {
            $this->getAdminData($admin, ['period' => $period])
                ->assertOk()
                ->assertJsonPath('data.filters.period', $period);
        }
    }

    public function test_custom_period_validation_is_strict(): void
    {
        $admin = $this->createUser('admin', $this->createBranch());

        $this->getAdminData($admin, ['period' => 'custom'])
            ->assertJsonValidationErrors(['date_from', 'date_to']);
        $this->getAdminData($admin, [
            'period' => 'custom',
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-19',
        ])->assertJsonValidationErrors('date_to');
        $this->getAdminData($admin, [
            'period' => 'custom',
            'date_from' => '2025-01-01',
            'date_to' => '2026-07-25',
        ])->assertJsonValidationErrors('date_to');
        $this->getAdminData($admin, [
            'period' => 'custom',
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-26',
        ])->assertJsonValidationErrors('date_to');
    }
}
