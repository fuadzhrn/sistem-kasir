<?php

namespace App\Services\Authorization;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class BranchAccessService
{
    public function canAccessBranch(User $user, Branch|int $branch): bool
    {
        $branchId = $this->branchId($branch);

        if ($branchId === null || ! $user->is_active) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        return $user->branch_id !== null && $user->branch_id === $branchId;
    }

    /**
     * @return array<int, int>
     */
    public function accessibleBranchIds(User $user): array
    {
        if (! $user->is_active) {
            return [];
        }

        if ($user->isOwner()) {
            return Branch::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all();
        }

        if ($user->branch_id === null) {
            return [];
        }

        return Branch::query()
            ->whereKey($user->branch_id)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * @throws AuthorizationException
     */
    public function resolveBranchId(User $user, ?int $requestedBranchId = null): int
    {
        if (! $user->is_active) {
            throw new AuthorizationException('Akun tidak dapat digunakan.');
        }

        if ($user->isOwner()) {
            if ($requestedBranchId === null) {
                throw new AuthorizationException('Cabang harus dipilih.');
            }

            $branchId = Branch::query()
                ->whereKey($requestedBranchId)
                ->where('is_active', true)
                ->value('id');

            if ($branchId === null) {
                throw new AuthorizationException('Cabang tidak tersedia.');
            }

            return (int) $branchId;
        }

        if ($user->branch_id === null) {
            throw new AuthorizationException('Cabang akun belum ditetapkan.');
        }

        $branchId = Branch::query()
            ->whereKey($user->branch_id)
            ->where('is_active', true)
            ->value('id');

        if ($branchId === null) {
            throw new AuthorizationException('Cabang akun tidak tersedia.');
        }

        return (int) $branchId;
    }

    public function isSameBranch(User $user, Branch|int $branch): bool
    {
        $branchId = $this->branchId($branch);

        return $branchId !== null
            && $user->branch_id !== null
            && $user->branch_id === $branchId;
    }

    private function branchId(Branch|int $branch): ?int
    {
        if ($branch instanceof Branch) {
            return $branch->exists ? (int) $branch->getKey() : null;
        }

        $branchId = Branch::query()->whereKey($branch)->value('id');

        return $branchId === null ? null : (int) $branchId;
    }
}
