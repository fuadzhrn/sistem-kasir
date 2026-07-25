<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;

class ExpensePolicy
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin'])
            && $this->branchAccess->canAccessBranch($user, $expense->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->view($user, $expense);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $user->is_active
            && $user->isOwner()
            && $this->branchAccess->canAccessBranch($user, $expense->branch_id);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->is_active
            && $user->isOwner()
            && $this->branchAccess->canAccessBranch($user, $expense->branch_id);
    }
}
