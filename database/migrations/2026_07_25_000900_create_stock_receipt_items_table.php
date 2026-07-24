<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('purchase_price', 18, 2);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();

            $table->index(['stock_receipt_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_items');
    }
};
