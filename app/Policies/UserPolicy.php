<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin', 'cashier']);
    }

    public function view(User $user, User $targetUser): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->branch_id !== null
                && $user->branch_id === $targetUser->branch_id
                && ! $targetUser->isOwner();
        }

        return $user->isCashier() && $user->is($targetUser);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->is_active && $user->isOwner() && $targetUser->exists;
    }

    public function activate(User $user, User $targetUser): bool
    {
        return $this->update($user, $targetUser);
    }

    public function deactivate(User $user, User $targetUser): bool
    {
        return $this->update($user, $targetUser);
    }

    public function resetPassword(User $user, User $targetUser): bool
    {
        return $user->is_active
            && $user->isOwner()
            && $targetUser->exists;
    }
}
