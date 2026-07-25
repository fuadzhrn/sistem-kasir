<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('from_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->string('status')->default('pending')->index();
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('source_quantity_before', 18, 3)->nullable();
            $table->decimal('source_quantity_after', 18, 3)->nullable();
            $table->decimal('destination_quantity_before', 18, 3)->nullable();
            $table->decimal('destination_quantity_after', 18, 3)->nullable();
            $table->decimal('destination_average_cost_before', 18, 2)->nullable();
            $table->decimal('destination_average_cost_after', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['product_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['from_branch_id', 'status', 'created_at']);
            $table->index(['to_branch_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
