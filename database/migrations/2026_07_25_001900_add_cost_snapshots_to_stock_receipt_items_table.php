<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_receipt_items', function (Blueprint $table) {
            $table->decimal('quantity_before', 18, 3)->after('subtotal');
            $table->decimal('quantity_after', 18, 3)->after('quantity_before');
            $table->decimal('average_cost_before', 18, 2)->after('quantity_after');
            $table->decimal('average_cost_after', 18, 2)->after('average_cost_before');
            $table->unique(
                ['stock_receipt_id', 'product_id'],
                'stock_receipt_items_receipt_product_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_receipt_items', function (Blueprint $table) {
            $table->dropUnique('stock_receipt_items_receipt_product_unique');
            $table->dropColumn([
                'quantity_before',
                'quantity_after',
                'average_cost_before',
                'average_cost_after',
            ]);
        });
    }
};
