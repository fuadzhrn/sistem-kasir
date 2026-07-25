<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class SalePolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || $user->branch_id !== null);
    }

    public function view(User $user, Sale $sale): bool
    {
        if (! $user->is_active || ! $this->branchAccess->canAccessBranch($user, $sale->branch_id)) {
            return false;
        }

        return ! $user->isCashier() || $sale->cashier_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || $user->branch_id !== null);
    }

    public function void(User $user, Sale $sale): bool
    {
        if (! $this->view($user, $sale) || ! $sale->canBeVoided()) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->isAdmin()) {
            return (int) $sale->branch_id === (int) $user->branch_id;
        }

        return $user->isCashier()
            && (int) $sale->branch_id === (int) $user->branch_id
            && (int) $sale->cashier_id === (int) $user->getKey();
    }

    public function print(User $user, Sale $sale): bool
    {
        return $this->view($user, $sale);
    }
}
