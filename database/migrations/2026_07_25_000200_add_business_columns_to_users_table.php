<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('branch_id')
                ->nullable()
                ->after('role_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('username')->after('name')->unique();
            $table->string('email')->nullable()->change();
            $table->boolean('is_active')->default(true)->after('password')->index();
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->index(['role_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id', 'branch_id']);
            $table->dropUnique(['username']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'role_id',
                'branch_id',
                'username',
                'is_active',
                'last_login_at',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
