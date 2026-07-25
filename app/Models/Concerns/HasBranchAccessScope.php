<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasBranchAccessScope
{
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwner()) {
            return $query;
        }

        if ($user->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->qualifyColumn('branch_id'),
            $user->branch_id,
        );
    }
}
