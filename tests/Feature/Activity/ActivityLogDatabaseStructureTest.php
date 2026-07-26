<?php

namespace Tests\Feature\Activity;

use Illuminate\Support\Facades\Schema;

class ActivityLogDatabaseStructureTest extends ActivityLogTestCase
{
    public function test_activity_log_metadata_and_required_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('activity_logs', 'metadata'));
        $indexNames = collect(Schema::getIndexes('activity_logs'))->pluck('name');

        foreach ([
            'activity_logs_branch_created_index',
            'activity_logs_user_created_index',
            'activity_logs_branch_action_created_index',
            'activity_logs_module_created_index',
        ] as $indexName) {
            $this->assertTrue($indexNames->contains($indexName), "Indeks {$indexName} tidak ditemukan.");
        }
    }

    public function test_stage_twenty_one_migration_can_roll_back_and_run_again(): void
    {
        $migration = require database_path('migrations/2026_07_26_030000_complete_activity_log_audit_structure.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('activity_logs', 'metadata'));
        $this->assertTrue(Schema::hasTable('activity_logs'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('activity_logs', 'metadata'));
        $this->assertTrue(
            collect(Schema::getIndexes('activity_logs'))
                ->pluck('name')
                ->contains('activity_logs_branch_action_created_index'),
        );
    }
}
