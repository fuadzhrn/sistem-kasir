<?php

namespace Tests\Feature\Activity;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ActivityLogTestCase extends TestCase
{
    use RefreshDatabase;

    protected function branch(string $code = 'AUD'): Branch
    {
        return Branch::factory()->create([
            'code' => $code,
            'name' => "Cabang {$code}",
            'is_active' => true,
        ]);
    }

    protected function user(string $roleSlug, ?Branch $branch = null, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst($roleSlug), 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $roleSlug === 'owner' ? null : $branch?->id,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function log(?User $actor, ?Branch $branch, array $attributes = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'branch_id' => $branch?->id,
            'action' => 'product_updated',
            'module' => 'products',
            'description' => 'Produk aman diperbarui.',
            'metadata' => ['safe' => 'nilai aman'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser Pengujian',
            ...$attributes,
        ]);
    }
}
