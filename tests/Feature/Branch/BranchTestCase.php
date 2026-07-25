<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class BranchTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createRole(string $slug): Role
    {
        return Role::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug), 'is_active' => true],
        );
    }

    protected function createUser(string $role, ?Branch $branch = null, array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => $this->createRole($role),
            'branch_id' => $role === 'owner' ? null : ($branch ?? Branch::factory()->create()),
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
