<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null));
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        return $user->isAdmin()
            && $user->branch_id !== null
            && $user->branch_id === $activityLog->branch_id;
    }
}
