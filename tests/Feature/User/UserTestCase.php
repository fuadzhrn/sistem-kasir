<?php

namespace Tests\Feature\User;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class UserTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createRole(string $slug, bool $active = true): Role
    {
        return Role::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug), 'is_active' => $active],
        );
    }

    protected function createUser(string $role, ?Branch $branch = null, array $attributes = []): User
    {
        return User::factory()->create([
            'role_id' => $this->createRole($role),
            'branch_id' => $role === 'owner' ? null : ($branch ?? Branch::factory()->create()),
            'password' => 'Password123',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function validPayload(string $role, ?Branch $branch = null, array $overrides = []): array
    {
        return [
            'name' => 'Pengguna Baru',
            'username' => 'pengguna.baru',
            'email' => 'pengguna.baru@example.test',
            'role_id' => $this->createRole($role)->id,
            'branch_id' => $role === 'owner' ? null : ($branch ?? Branch::factory()->create())->id,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
            ...$overrides,
        ];
    }
}
