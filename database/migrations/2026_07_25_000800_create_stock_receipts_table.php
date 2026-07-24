<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('receipt_number')->unique();
            $table->date('receipt_date')->index();
            $table->string('supplier_name')->nullable();
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};
