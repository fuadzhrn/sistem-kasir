<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['owner', 'admin', 'cashier']);
    }

    public function view(User $user, Product $product): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasAnyRole(['owner', 'admin'])) {
            return true;
        }

        return $user->isCashier() && $product->is_active;
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

    public function delete(User $user, Product $product): bool
    {
        if (! $user->is_active || ! $user->isOwner()) {
            return false;
        }

        return ! $product->saleItems()->exists()
            && ! $product->stockReceiptItems()->exists()
            && ! $product->branchStocks()->exists()
            && ! $product->stockMovements()->exists()
            && ! $product->priceHistories()->exists();
    }
}
