<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_voids', function (Blueprint $table): void {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('sale_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('voided_by')
                ->nullable()
                ->after('requested_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->decimal('original_subtotal', 18, 2)->nullable()->after('reason');
            $table->decimal('original_discount_amount', 18, 2)->nullable()->after('original_subtotal');
            $table->decimal('original_total', 18, 2)->nullable()->after('original_discount_amount');
            $table->decimal('original_total_cost', 18, 2)->nullable()->after('original_total');
            $table->decimal('original_gross_profit', 18, 2)->nullable()->after('original_total_cost');
            $table->string('payment_method_name')->nullable()->after('original_gross_profit');
            $table->boolean('refund_confirmed')->default(false)->after('payment_method_name');
            $table->text('notes')->nullable()->after('refund_confirmed');

            $table->unique('sale_id', 'sale_voids_sale_id_unique');
            $table->index(['branch_id', 'voided_at'], 'sale_voids_branch_voided_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('sale_voids', function (Blueprint $table): void {
            $table->dropUnique('sale_voids_sale_id_unique');
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['branch_id']);
            $table->dropIndex('sale_voids_branch_voided_at_index');
            $table->dropColumn([
                'branch_id',
                'voided_by',
                'voided_at',
                'original_subtotal',
                'original_discount_amount',
                'original_total',
                'original_total_cost',
                'original_gross_profit',
                'payment_method_name',
                'refund_confirmed',
                'notes',
            ]);
        });
    }
};
