<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('checkout_token', 64)
                ->nullable()
                ->after('invoice_number')
                ->unique('sales_checkout_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_checkout_token_unique');
            $table->dropColumn('checkout_token');
        });
    }
};
