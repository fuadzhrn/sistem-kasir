<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Sale;

class OwnerDashboardTopProductsTest extends OwnerDashboardTestCase
{
    public function test_top_products_rank_by_net_sales_with_snapshots_quantity_and_receipt_count(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('TOP');
        $productA = $this->createProduct('TOP-A', ['name' => 'Produk A']);
        $productB = $this->createProduct('TOP-B', ['name' => 'Produk B', 'is_active' => false]);
        $this->createSale($branch, $owner, [], $productA, [
            'quantity' => '3.500',
            'subtotal' => '350000.00',
        ]);
        $this->createSale($branch, $owner, [], $productA, [
            'quantity' => '1.000',
            'subtotal' => '100000.00',
        ]);
        $this->createSale($branch, $owner, [], $productB, [
            'quantity' => '10.000',
            'subtotal' => '200000.00',
        ]);
        $this->createSale($branch, $owner, ['status' => Sale::STATUS_VOIDED], $productB, [
            'subtotal' => '900000.00',
        ]);

        $this->getDashboardData($owner)
            ->assertOk()
            ->assertJsonCount(2, 'data.top_products')
            ->assertJsonPath('data.top_products.0.code', 'TOP-A')
            ->assertJsonPath('data.top_products.0.quantity', '4,5')
            ->assertJsonPath('data.top_products.0.receipt_count', 2)
            ->assertJsonPath('data.top_products.0.net_sales', '450000');
    }

    public function test_top_products_are_limited_to_ten_and_follow_branch_filter(): void
    {
        $owner = $this->createUser('owner');
        $branchA = $this->createBranch('TPA');
        $branchB = $this->createBranch('TPB');

        for ($index = 1; $index <= 11; $index++) {
            $this->createSale(
                $branchA,
                $owner,
                [],
                $this->createProduct('LIM-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)),
            );
        }
        $this->createSale($branchB, $owner);

        $this->getDashboardData($owner, ['branch_id' => $branchA->id])
            ->assertOk()
            ->assertJsonCount(10, 'data.top_products');
    }
}
