<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->isOwner()
            || $stockTransfer->from_branch_id === $user->branch_id
            || $stockTransfer->to_branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function complete(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->is_active && $user->isOwner() && $stockTransfer->isPending();
    }

    public function reject(User $user, StockTransfer $stockTransfer): bool
    {
        return $this->complete($user, $stockTransfer);
    }

    public function cancel(User $user, StockTransfer $stockTransfer): bool
    {
        if (! $user->is_active || ! $stockTransfer->isPending()) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        return $user->isAdmin()
            && $user->branch_id === $stockTransfer->from_branch_id
            && $user->getKey() === $stockTransfer->requested_by;
    }

    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        return false;
    }

    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        return false;
    }
}
