<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('adjustment_type')->index();
            $table->decimal('quantity', 18, 3);
            $table->decimal('target_quantity', 18, 3)->nullable();
            $table->decimal('quantity_before', 18, 3);
            $table->decimal('quantity_change', 18, 3);
            $table->decimal('quantity_after', 18, 3);
            $table->decimal('unit_cost', 18, 2);
            $table->text('reason');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['branch_id', 'created_at']);
            $table->index(['branch_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
