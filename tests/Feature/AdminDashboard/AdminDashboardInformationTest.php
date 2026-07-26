<?php

namespace Tests\Feature\AdminDashboard;

use App\Models\Expense;
use App\Models\Sale;

class AdminDashboardInformationTest extends AdminDashboardTestCase
{
    public function test_admin_information_sections_only_contain_its_branch(): void
    {
        $branch = $this->createBranch('INA');
        $otherBranch = $this->createBranch('INB');
        $admin = $this->createUser('admin', $branch);
        $cashier = $this->createUser('cashier', $branch);
        $otherCashier = $this->createUser('cashier', $otherBranch);
        $product = $this->createProduct('BEST-A');
        $otherProduct = $this->createProduct('SECRET-B', ['name' => 'Produk Rahasia']);
        $this->createSale($branch, $cashier, product: $product);
        $this->createSale($branch, $cashier, [
            'status' => Sale::STATUS_VOIDED,
            'invoice_number' => 'VOID-A',
        ]);
        $this->createSale($otherBranch, $otherCashier, product: $otherProduct);
        $this->createStock($branch, $product);
        $this->createStock($otherBranch, $otherProduct);
        $this->createExpense($branch, $admin, Expense::STATUS_PENDING);

        $response = $this->getAdminData($admin)->assertOk();
        $response->assertJsonCount(1, 'data.top_products');
        $response->assertJsonPath('data.top_products.0.code', 'BEST-A');
        $response->assertJsonCount(1, 'data.low_stocks');
        $response->assertJsonPath('data.low_stocks.0.product_code', 'BEST-A');
        $response->assertJsonFragment(['invoice_number' => 'VOID-A']);
        $response->assertJsonFragment(['status' => 'Menunggu Persetujuan']);
        $response->assertDontSee('Produk Rahasia');
    }
}
