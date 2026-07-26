<?php

namespace Tests\Feature\Report;

class ReportDataSecurityTest extends ReportTestCase
{
    public function test_admin_never_receives_inventory_cost_or_purchase_price_history(): void
    {
        $branch = $this->createBranch('RDS');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);
        $product = $this->createProduct('SECRET-COST');
        $this->createStock($branch, $product, '3.000');
        $this->createStockMovement($branch, $admin, $product);
        $this->createPriceHistory($owner, $product);

        $this->getReport($owner, 'stocks')->assertSee('Rp50.000');
        $this->getReport($admin, 'stocks')
            ->assertOk()
            ->assertDontSee('Average Cost')
            ->assertDontSee('Nilai Persediaan')
            ->assertDontSee('Rp50.000');
        $this->getPrintReport($admin, 'stock-movements')
            ->assertOk()
            ->assertDontSee('Unit Cost')
            ->assertDontSee('Rp60.000');
        $this->getReport($owner, 'price-histories')->assertSee('Harga Beli Lama');
        $this->getReport($admin, 'price-histories')
            ->assertOk()
            ->assertDontSee('Harga Beli Lama')
            ->assertDontSee('Rp55.000');
        $this->getPrintReport($admin, 'price-histories')
            ->assertOk()
            ->assertDontSee('Harga Beli Lama')
            ->assertDontSee('Rp55.000');
    }
}
