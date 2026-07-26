<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('description');
            $table->index(['branch_id', 'created_at'], 'activity_logs_branch_created_index');
            $table->index(['user_id', 'created_at'], 'activity_logs_user_created_index');
            $table->index(['branch_id', 'action', 'created_at'], 'activity_logs_branch_action_created_index');
            $table->index(['module', 'created_at'], 'activity_logs_module_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_branch_created_index');
            $table->dropIndex('activity_logs_user_created_index');
            $table->dropIndex('activity_logs_branch_action_created_index');
            $table->dropIndex('activity_logs_module_created_index');
            $table->dropColumn('metadata');
        });
    }
};
