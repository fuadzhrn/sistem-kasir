<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;

class ActivityLogImmutabilityTest extends ActivityLogTestCase
{
    public function test_activity_log_cannot_be_updated_or_deleted_through_model(): void
    {
        $owner = $this->user('owner');
        $log = $this->log($owner, null);

        $this->assertFalse($log->update(['description' => 'Diubah secara ilegal']));
        $this->assertFalse($log->delete());
        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'description' => 'Produk aman diperbarui.',
        ]);
    }

    public function test_policy_denies_all_mutating_abilities(): void
    {
        $owner = $this->user('owner');
        $log = $this->log($owner, null);

        $this->assertFalse($owner->can('create', ActivityLog::class));
        $this->assertFalse($owner->can('update', $log));
        $this->assertFalse($owner->can('delete', $log));
    }
}
