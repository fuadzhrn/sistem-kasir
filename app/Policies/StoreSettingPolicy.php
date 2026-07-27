<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class StoreSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function updateLogo(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function deleteLogo(User $user): bool
    {
        return $this->viewAny($user);
    }
}
