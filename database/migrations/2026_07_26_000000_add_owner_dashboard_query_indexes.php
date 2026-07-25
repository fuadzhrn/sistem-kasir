<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->index(
                ['status', 'transaction_date'],
                'sales_status_transaction_date_index',
            );
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(
                ['status', 'expense_date'],
                'expenses_status_expense_date_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_status_expense_date_index');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex('sales_status_transaction_date_index');
        });
    }
};
