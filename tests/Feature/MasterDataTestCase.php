<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class MasterDataTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        $roleModel = Role::query()->firstOrCreate(
            ['slug' => $role],
            ['name' => ucfirst($role), 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $roleModel,
            'branch_id' => $role === 'owner' ? null : Branch::factory()->create(),
            'is_active' => true,
        ]);
    }
}
