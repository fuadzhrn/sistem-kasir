<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['owner', 'admin']);
    }

    public function view(User $user, Unit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function updateStatus(User $user, Unit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->is_active && $user->isOwner();
    }
}
