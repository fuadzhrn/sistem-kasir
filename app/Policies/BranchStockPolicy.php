<?php

namespace App\Policies;

use App\Models\BranchStock;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class BranchStockPolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || $user->branch_id !== null);
    }

    public function view(User $user, BranchStock $branchStock): bool
    {
        return $this->branchAccess->canAccessBranch($user, $branchStock->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function update(User $user, BranchStock $branchStock): bool
    {
        return $this->canManage($user, $branchStock);
    }

    public function adjust(User $user, BranchStock $branchStock): bool
    {
        return $this->canManage($user, $branchStock);
    }

    private function canManage(User $user, BranchStock $branchStock): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin'])
            && $this->branchAccess->canAccessBranch($user, $branchStock->branch_id);
    }
}
