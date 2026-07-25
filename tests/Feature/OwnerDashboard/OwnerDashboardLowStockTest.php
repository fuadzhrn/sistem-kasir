<?php

namespace Tests\Feature\OwnerDashboard;

class OwnerDashboardLowStockTest extends OwnerDashboardTestCase
{
    public function test_low_stock_uses_current_condition_status_order_and_hides_average_cost(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('LOW');
        $empty = $this->createProduct('EMPTY', ['minimum_stock' => '5.000']);
        $negative = $this->createProduct('NEGATIVE', ['minimum_stock' => '5.000']);
        $low = $this->createProduct('LOW', ['minimum_stock' => '5.000']);
        $equal = $this->createProduct('EQUAL', ['minimum_stock' => '5.000']);
        $safe = $this->createProduct('SAFE', ['minimum_stock' => '5.000']);
        $this->createStock($branch, $empty, '0.000');
        $this->createStock($branch, $negative, '-1.000');
        $this->createStock($branch, $low, '2.000');
        $this->createStock($branch, $equal, '5.000');
        $this->createStock($branch, $safe, '5.001');

        $response = $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-02',
        ])->assertOk();

        $response->assertJsonCount(4, 'data.low_stocks')
            ->assertJsonPath('data.low_stocks.0.status', 'Habis');
        $this->assertNotContains(
            'SAFE',
            array_column($response->json('data.low_stocks'), 'product_code'),
        );
        $this->assertStringNotContainsString('average_cost', $response->getContent());
    }
}
