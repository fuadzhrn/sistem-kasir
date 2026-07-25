<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function view(User $user, Product $product): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['owner', 'admin']);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->is_active
            && $product->exists
            && $user->hasAnyRole(['owner', 'admin']);
    }

    public function updatePrice(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function updatePurchasePrice(User $user, Product $product): bool
    {
        return $user->is_active && $product->exists && $user->isOwner();
    }

    public function updateStatus(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function removeImage(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function viewPriceHistory(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function viewForSale(User $user, Product $product): bool
    {
        return $user->is_active && $product->is_active;
    }
}
