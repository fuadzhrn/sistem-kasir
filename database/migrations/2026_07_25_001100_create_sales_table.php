<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->dateTime('transaction_date')->index();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->decimal('change_amount', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->decimal('gross_profit', 18, 2)->default(0);
            $table->string('payment_method_name');
            $table->string('status')->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'transaction_date']);
            $table->index(['cashier_id', 'transaction_date']);
            $table->index(['branch_id', 'status', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
