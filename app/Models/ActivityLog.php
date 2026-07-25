<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'branch_id',
    'action',
    'module',
    'reference_type',
    'reference_id',
    'description',
    'ip_address',
    'user_agent',
])]
class ActivityLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOwner()) {
            return $query;
        }

        if (! $user->isAdmin() || $user->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->qualifyColumn('branch_id'),
            $user->branch_id,
        );
    }

    protected static function booted(): void
    {
        static::updating(fn (): bool => false);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
