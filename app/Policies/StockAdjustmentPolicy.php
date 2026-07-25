<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class StockAdjustmentPolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function view(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $this->viewAny($user)
            && $this->branchAccess->canAccessBranch($user, $stockAdjustment->branch_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StockAdjustment $stockAdjustment): bool
    {
        return false;
    }

    public function delete(User $user, StockAdjustment $stockAdjustment): bool
    {
        return false;
    }
}
