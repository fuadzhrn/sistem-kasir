<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

class ActivityLogAuthenticationTest extends ActivityLogTestCase
{
    public function test_login_success_failure_and_logout_are_audited_without_password(): void
    {
        $branch = $this->branch();
        $user = $this->user('admin', $branch, [
            'username' => 'admin.audit',
            'password' => Hash::make('Password-Uji-123'),
        ]);

        $this->post(route('login.store'), [
            'login_role' => 'admin',
            'login' => 'admin.audit',
            'password' => 'Password-Uji-123',
        ])->assertRedirect();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login_success',
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'logout', 'user_id' => $user->id]);

        $this->post(route('login.store'), [
            'login_role' => 'admin',
            'login' => 'admin.audit',
            'password' => 'Password-Salah-Rahasia',
        ])->assertSessionHasErrors('login');
        $failed = ActivityLog::query()->where('action', 'login_failed')->latest('id')->firstOrFail();

        $this->assertNull($failed->user_id);
        $this->assertNull($failed->branch_id);
        $this->assertStringNotContainsString('Password-Salah-Rahasia', json_encode($failed->metadata));
        $this->assertArrayNotHasKey('password', $failed->metadata);
    }
}
