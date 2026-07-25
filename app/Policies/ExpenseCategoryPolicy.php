<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['owner', 'admin']);
    }

    public function view(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $this->viewAny($user);
    }

    public function updateStatus(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->is_active && $user->isOwner();
    }
}
