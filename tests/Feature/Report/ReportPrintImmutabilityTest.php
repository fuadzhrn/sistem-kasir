<?php

namespace Tests\Feature\Report;

use Illuminate\Support\Facades\DB;

class ReportPrintImmutabilityTest extends ReportTestCase
{
    public function test_opening_screen_and_print_does_not_mutate_business_tables(): void
    {
        $branch = $this->createBranch('RIM');
        $owner = $this->createUser('owner');
        $product = $this->createProduct('RIM-001');
        $this->createStock($branch, $product);
        $this->createSale($branch, $owner, product: $product);
        $tables = ['sales', 'sale_items', 'branch_stocks', 'stock_movements', 'activity_logs'];
        $before = collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()]);

        $this->getReport($owner, 'stocks')->assertOk();
        $this->getPrintReport($owner, 'stocks')->assertOk();

        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "Tabel {$table} berubah.");
        }
    }
}
