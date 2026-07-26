<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index('created_at', 'stock_movements_created_at_index');
            $table->index('reference_id', 'stock_movements_reference_id_index');
        });

        Schema::table('sale_voids', function (Blueprint $table): void {
            $table->index('voided_at', 'sale_voids_voided_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex('stock_movements_created_at_index');
            $table->dropIndex('stock_movements_reference_id_index');
        });

        Schema::table('sale_voids', function (Blueprint $table): void {
            $table->dropIndex('sale_voids_voided_at_index');
        });
    }
};
