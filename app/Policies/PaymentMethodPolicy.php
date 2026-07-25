<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['owner', 'admin']);
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isOwner();
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->create($user);
    }

    public function updateStatus(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->create($user);
    }
}
