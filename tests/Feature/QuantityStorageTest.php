<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Support\Format\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuantityStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantity_columns_remain_decimal_with_three_digit_scale(): void
    {
        foreach ([
            ['products', 'minimum_stock'],
            ['branch_stocks', 'quantity'],
            ['stock_movements', 'quantity_before'],
            ['stock_movements', 'quantity_change'],
            ['stock_movements', 'quantity_after'],
            ['stock_receipt_items', 'quantity'],
            ['stock_adjustments', 'quantity'],
            ['stock_transfers', 'quantity'],
            ['sale_items', 'quantity'],
        ] as [$table, $column]) {
            $this->assertContains(
                Schema::getColumnType($table, $column),
                ['decimal', 'numeric'],
                "{$table}.{$column} harus tetap bertipe decimal/numeric.",
            );
        }

        $this->assertStringContainsString(
            "\$table->decimal('quantity', 18, 3)",
            (string) file_get_contents(database_path(
                'migrations/2026_07_25_000600_create_branch_stocks_table.php',
            )),
        );
    }

    public function test_formatting_fractional_quantity_does_not_modify_database_value(): void
    {
        $stock = BranchStock::query()->create([
            'branch_id' => Branch::factory()->create()->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => '1000.500',
            'average_cost' => '12500.00',
        ]);

        $this->assertSame('1000.500', $stock->fresh()->quantity);
        $this->assertSame('1.000,5', Quantity::format($stock->quantity));
        $this->assertSame('1000.500', $stock->fresh()->quantity);
    }
}
