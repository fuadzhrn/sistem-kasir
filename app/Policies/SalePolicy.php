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

    public function requestVoid(User $user, Sale $sale): bool
    {
        return $this->view($user, $sale);
    }

    public function approveVoid(User $user, Sale $sale): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin'])
            && $this->branchAccess->canAccessBranch($user, $sale->branch_id);
    }

    public function print(User $user, Sale $sale): bool
    {
        return $this->view($user, $sale);
    }
}
