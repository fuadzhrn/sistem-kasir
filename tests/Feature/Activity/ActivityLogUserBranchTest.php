<?php

namespace Tests\Feature\Activity;

use App\Models\Role;
use App\Services\User\UserService;

class ActivityLogUserBranchTest extends ActivityLogTestCase
{
    public function test_user_branch_change_is_logged_in_new_branch_context(): void
    {
        $owner = $this->user('owner');
        $branchA = $this->branch('UA1');
        $branchB = $this->branch('UB1');
        $cashier = $this->user('cashier', $branchA);
        $cashierRole = Role::query()->where('slug', 'cashier')->firstOrFail();

        $this->actingAs($owner);
        app(UserService::class)->update($cashier, [
            'role_id' => $cashierRole->id,
            'branch_id' => $branchB->id,
            'name' => $cashier->name,
            'username' => $cashier->username,
            'email' => $cashier->email,
        ], $owner);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_branch_changed',
            'branch_id' => $branchB->id,
            'reference_id' => $cashier->id,
        ]);
    }
}
