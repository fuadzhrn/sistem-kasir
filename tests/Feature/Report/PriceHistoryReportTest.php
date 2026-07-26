<?php

namespace Tests\Feature\Report;

use App\Models\PriceHistory;

class PriceHistoryReportTest extends ReportTestCase
{
    public function test_price_history_report_is_immutable_and_admin_only_sees_selling_prices(): void
    {
        $branch = $this->createBranch('RPH');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct('PRICE-001');
        $this->createPriceHistory($owner, $product);
        $before = PriceHistory::query()->count();

        $this->getReport($owner, 'price-histories', ['search' => 'PRICE-001'])
            ->assertOk()
            ->assertSee('Harga Beli Lama')
            ->assertSee('Rp50.000')
            ->assertSee('Rp80.000');
        $this->getReport($admin, 'price-histories')
            ->assertOk()
            ->assertDontSee('Harga Beli Lama')
            ->assertDontSee('Rp50.000')
            ->assertSee('Rp80.000');
        $this->getPrintReport($admin, 'price-histories')
            ->assertOk()
            ->assertDontSee('Harga Beli Lama');
        $this->assertSame($before, PriceHistory::query()->count());
    }

    public function test_admin_cannot_sort_or_filter_by_purchase_price(): void
    {
        $branch = $this->createBranch('RPA');
        $admin = $this->createUser('admin', $branch);

        $this->getReport($admin, 'price-histories', ['sort' => 'purchase_change'])
            ->assertRedirect()->assertSessionHasErrors('sort');
        $this->getReport($admin, 'price-histories', ['change_type' => 'purchase'])
            ->assertOk()
            ->assertDontSee('Harga Beli');
    }
}
