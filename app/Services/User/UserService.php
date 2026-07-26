<?php

namespace App\Services\User;

use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = $this->activeRole((int) $data['role_id']);
            $branchId = $this->resolveBranchId($role, $data['branch_id'] ?? null);

            return User::query()->create([
                'role_id' => $role->getKey(),
                'branch_id' => $branchId,
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'last_login_at' => null,
            ]);
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $target = User::query()->with('role')->lockForUpdate()->findOrFail($user->getKey());
            $role = $this->activeRole((int) $data['role_id']);
            $this->ensureOwnerProtection($target, ['role_id' => $role->getKey()], $actor);
            $branchId = $this->resolveBranchId($role, $data['branch_id'] ?? null);

            $target->update([
                'role_id' => $role->getKey(),
                'branch_id' => $branchId,
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
            ]);

            return $target->refresh()->load(['role', 'branch']);
        });
    }

    public function updateStatus(User $user, bool $isActive, User $actor): User
    {
        return DB::transaction(function () use ($user, $isActive, $actor): User {
            $target = User::query()->with(['role', 'branch'])->lockForUpdate()->findOrFail($user->getKey());

            $this->ensureOwnerProtection($target, ['is_active' => $isActive], $actor);

            if ($isActive) {
                if (! $target->role?->is_active) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Akun tidak dapat diaktifkan karena role tidak aktif.',
                    ]);
                }

                if (! $target->isOwner() && ! $target->branch?->is_active) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Akun tidak dapat diaktifkan karena cabang tidak aktif.',
                    ]);
                }
            }

            $target->update(['is_active' => $isActive]);

            return $target->refresh();
        });
    }

    public function resetPassword(User $user, string $password, User $actor): void
    {
        DB::transaction(function () use ($user, $password, $actor): void {
            $target = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($actor->is($target)) {
                throw ValidationException::withMessages([
                    'password' => 'Gunakan halaman akun untuk mengganti kata sandi Owner sendiri.',
                ]);
            }

            $target->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            $this->auditLog->record(
                action: 'user_password_reset',
                module: 'users',
                description: "Kata sandi pengguna {$target->username} direset oleh Owner.",
                actor: $actor,
                branch: $target->branch_id,
                reference: $target,
                metadata: [
                    'target_user_id' => $target->getKey(),
                    'target_username' => $target->username,
                    'target_role' => $target->role?->slug,
                ],
            );
        });
    }

    public function ensureOwnerProtection(User $target, array $changes, User $actor): void
    {
        $newStatus = (bool) ($changes['is_active'] ?? $target->is_active);
        $newRoleId = (int) ($changes['role_id'] ?? $target->role_id);

        if ($actor->is($target) && ! $newStatus) {
            throw ValidationException::withMessages([
                'is_active' => 'Owner tidak dapat menonaktifkan akun yang sedang digunakan.',
            ]);
        }

        $ownerRoleId = Role::query()->where('slug', 'owner')->value('id');

        if ($actor->is($target) && $newRoleId !== $target->role_id) {
            throw ValidationException::withMessages([
                'role_id' => 'Pengguna tidak dapat mengubah role akun yang sedang digunakan.',
            ]);
        }

        $removesActiveOwner = $target->is_active
            && $target->role_id === $ownerRoleId
            && (! $newStatus || $newRoleId !== $ownerRoleId);

        if ($removesActiveOwner && $this->activeOwnerCount() <= 1) {
            $field = ! $newStatus ? 'is_active' : 'role_id';

            throw ValidationException::withMessages([
                $field => 'Owner aktif terakhir tidak dapat dinonaktifkan atau diubah rolenya.',
            ]);
        }
    }

    public function activeOwnerCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'owner'))
            ->lockForUpdate()
            ->pluck('id')
            ->count();
    }

    private function activeRole(int $roleId): Role
    {
        $role = Role::query()
            ->whereKey($roleId)
            ->where('is_active', true)
            ->whereIn('slug', ['owner', 'admin', 'cashier'])
            ->first();

        if ($role === null) {
            throw ValidationException::withMessages([
                'role_id' => 'Role aktif yang dipilih tidak tersedia.',
            ]);
        }

        return $role;
    }

    private function resolveBranchId(Role $role, mixed $branchId): ?int
    {
        if ($role->slug === 'owner') {
            return null;
        }

        $resolvedBranchId = DB::table('branches')
            ->where('id', $branchId)
            ->where('is_active', true)
            ->value('id');

        if ($resolvedBranchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'Admin dan Kasir wajib ditempatkan pada cabang aktif.',
            ]);
        }

        return (int) $resolvedBranchId;
    }
}
