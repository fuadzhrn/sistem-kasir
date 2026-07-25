<?php

namespace Tests\Feature\OwnerDashboard;

class OwnerDashboardFilterTest extends OwnerDashboardTestCase
{
    public function test_branch_and_all_supported_period_filters_are_validated(): void
    {
        $owner = $this->createUser('owner');
        $inactiveBranch = $this->createBranch('OFF', ['is_active' => false]);

        foreach (['today', 'this_week', 'this_month', 'this_year'] as $period) {
            $this->getDashboardData($owner, [
                'branch_id' => $inactiveBranch->id,
                'period' => $period,
            ])
                ->assertOk()
                ->assertJsonPath('data.filters.branch_id', $inactiveBranch->id)
                ->assertJsonPath('data.filters.period', $period);
        }

        $this->getDashboardData($owner, ['branch_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
        $this->getDashboardData($owner, ['period' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }

    public function test_custom_range_requires_valid_dates_not_longer_than_366_days(): void
    {
        $owner = $this->createUser('owner');

        $this->getDashboardData($owner, ['period' => 'custom'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_from', 'date_to']);
        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-10',
        ])->assertJsonValidationErrors('date_to');
        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2025-07-24',
            'date_to' => '2026-07-25',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to')
            ->assertJsonFragment([
                'Rentang dashboard maksimal 366 hari. Gunakan modul laporan untuk periode yang lebih panjang.',
            ]);
        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-25',
        ])
            ->assertOk()
            ->assertJsonPath('data.filters.date_from', '2026-07-01')
            ->assertJsonPath('data.filters.date_to', '2026-07-25');
    }
}
