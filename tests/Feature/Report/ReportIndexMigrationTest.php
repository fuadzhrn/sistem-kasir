<?php

namespace Tests\Feature\Report;

use Illuminate\Support\Facades\Schema;

class ReportIndexMigrationTest extends ReportTestCase
{
    public function test_report_indexes_can_be_rolled_back_and_migrated_again(): void
    {
        $migration = require database_path(
            'migrations/2026_07_26_020000_add_report_query_indexes.php',
        );

        $this->assertTrue(Schema::hasIndex('stock_movements', 'stock_movements_created_at_index'));
        $this->assertTrue(Schema::hasIndex('stock_movements', 'stock_movements_reference_id_index'));
        $this->assertTrue(Schema::hasIndex('sale_voids', 'sale_voids_voided_at_index'));

        $migration->down();

        $this->assertFalse(Schema::hasIndex('stock_movements', 'stock_movements_created_at_index'));
        $this->assertFalse(Schema::hasIndex('stock_movements', 'stock_movements_reference_id_index'));
        $this->assertFalse(Schema::hasIndex('sale_voids', 'sale_voids_voided_at_index'));

        $migration->up();

        $this->assertTrue(Schema::hasIndex('stock_movements', 'stock_movements_created_at_index'));
        $this->assertTrue(Schema::hasIndex('stock_movements', 'stock_movements_reference_id_index'));
        $this->assertTrue(Schema::hasIndex('sale_voids', 'sale_voids_voided_at_index'));
    }
}
