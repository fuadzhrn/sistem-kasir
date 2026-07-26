<?php

namespace Tests\Feature\Report;

use Illuminate\Support\Facades\DB;

class ReportPrintTest extends ReportTestCase
{
    public function test_all_print_routes_work_and_load_print_assets(): void
    {
        $owner = $this->createUser('owner');

        foreach ($this->reportSlugs() as $slug) {
            $this->getPrintReport($owner, $slug)
                ->assertOk()
                ->assertSee('assets/css/print/report.css', false)
                ->assertSee('assets/js/pages/report-print.js', false);
        }
    }

    public function test_print_over_two_thousand_rows_is_rejected_safely(): void
    {
        $branch = $this->createBranch('RPL');
        $owner = $this->createUser('owner');
        $product = $this->createProduct('RPL-001');
        ['sale' => $sale] = $this->createSale($branch, $owner, product: $product);
        $rows = [];

        for ($index = 0; $index < 2000; $index++) {
            $rows[] = [
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'product_code' => $product->code,
                'product_name' => "Produk Cetak {$index}",
                'unit_name' => 'Kilogram',
                'quantity' => '1.000',
                'selling_price' => '10000.00',
                'cost_price' => '5000.00',
                'discount_amount' => '0.00',
                'subtotal' => '10000.00',
                'profit' => '5000.00',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('sale_items')->insert($chunk);
        }

        $this->getPrintReport($owner, 'sales')
            ->assertUnprocessable()
            ->assertSee('melebihi 2.000', false)
            ->assertDontSee('SQLSTATE');
    }
}
