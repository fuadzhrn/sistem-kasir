<?php

namespace Tests\Feature\Activity;

class ActivityLogBranchIsolationTest extends ActivityLogTestCase
{
    public function test_admin_only_sees_own_branch_and_not_global_activity(): void
    {
        $branchA = $this->branch('A01');
        $branchB = $this->branch('B01');
        $admin = $this->user('admin', $branchA);
        $logA = $this->log($admin, $branchA, ['description' => 'Hanya Cabang A01']);
        $logB = $this->log(null, $branchB, ['description' => 'Rahasia Cabang B01']);
        $global = $this->log(null, null, ['description' => 'Rahasia Global Owner']);

        $response = $this->actingAs($admin)->get(route('activities.index'));

        $response->assertOk()
            ->assertSee($logA->description)
            ->assertDontSee($logB->description)
            ->assertDontSee($global->description);
        $this->actingAs($admin)->get(route('activities.show', $logB))->assertNotFound();
        $this->actingAs($admin)->get(route('activities.show', $global))->assertNotFound();
    }
}
