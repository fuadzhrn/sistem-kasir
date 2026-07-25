<?php

namespace App\Services\Cashier;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class CashierContextService
{
    public function resolveBranch(User $user, ?int $requestedBranchId = null): Branch
    {
        if (! $user->is_active) {
            throw new AuthorizationException('Akun tidak dapat digunakan.');
        }

        $branchId = $user->isOwner() ? $requestedBranchId : $user->branch_id;

        if ($branchId === null) {
            throw new AuthorizationException(
                $user->isOwner()
                    ? 'Owner harus memilih cabang aktif.'
                    : 'Cabang akun belum ditetapkan.',
            );
        }

        $branch = Branch::query()
            ->whereKey($branchId)
            ->where('is_active', true)
            ->first(['id', 'code', 'name', 'is_active']);

        if ($branch === null) {
            throw new AuthorizationException('Cabang kasir tidak tersedia atau tidak aktif.');
        }

        return $branch;
    }

    public function availableBranches(User $user): Collection
    {
        if (! $user->is_active) {
            return collect();
        }

        $query = Branch::query()->where('is_active', true);

        if (! $user->isOwner()) {
            $query->whereKey($user->branch_id);
        }

        return $query->orderBy('name')->get(['id', 'code', 'name']);
    }

    public function canSwitchBranch(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }
}
