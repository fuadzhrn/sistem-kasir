<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('old_purchase_price', 18, 2);
            $table->decimal('new_purchase_price', 18, 2);
            $table->decimal('old_selling_price', 18, 2);
            $table->decimal('new_selling_price', 18, 2);
            $table->text('reason')->nullable();
            $table->timestamp('changed_at')->index();
            $table->timestamps();

            $table->index(['product_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
