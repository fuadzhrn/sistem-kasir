<?php

namespace App\Policies;

use App\Models\StockReceipt;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class StockReceiptPolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function view(User $user, StockReceipt $stockReceipt): bool
    {
        return $this->viewAny($user)
            && $this->branchAccess->canAccessBranch($user, $stockReceipt->branch_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StockReceipt $stockReceipt): bool
    {
        return false;
    }

    public function delete(User $user, StockReceipt $stockReceipt): bool
    {
        return false;
    }
}
