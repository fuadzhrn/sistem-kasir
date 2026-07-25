<?php

namespace Tests\Feature\OwnerDashboard;

class OwnerDashboardPageTest extends OwnerDashboardTestCase
{
    public function test_owner_page_and_data_endpoint_use_default_all_branches_this_month(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch();
        $this->createSale($branch, $owner);

        $this->actingAs($owner)
            ->get(route('dashboard.owner'))
            ->assertOk()
            ->assertSee('Dashboard Owner')
            ->assertSee('Semua Cabang')
            ->assertSee('Bulan Ini');

        $this->getDashboardData($owner)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.filters.branch_id', null)
            ->assertJsonPath('data.filters.period', 'this_month')
            ->assertJsonPath('data.cards.net_sales.value', '180000.00');
    }
}
