<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class StockMovementPolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $this->viewAny($user)
            && $this->branchAccess->canAccessBranch($user, $stockMovement->branch_id);
    }
}
