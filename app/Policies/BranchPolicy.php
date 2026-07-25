<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class BranchPolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin'])
            && $this->branchAccess->canAccessBranch($user, $branch);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->is_active && $user->isOwner() && $branch->exists;
    }

    public function updateStatus(User $user, Branch $branch): bool
    {
        return $this->update($user, $branch);
    }

    public function delete(User $user, Branch $branch): bool
    {
        if (! $user->is_active || ! $user->isOwner()) {
            return false;
        }

        return ! $branch->users()->exists()
            && ! $branch->sales()->exists()
            && ! $branch->expenses()->exists()
            && ! $branch->branchStocks()->exists()
            && ! $branch->stockReceipts()->exists()
            && ! $branch->stockMovements()->exists();
    }
}
