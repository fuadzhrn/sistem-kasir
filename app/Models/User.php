<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'role_id',
    'branch_id',
    'name',
    'username',
    'email',
    'password',
    'is_active',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function updatedProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'updated_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function stockReceipts(): HasMany
    {
        return $this->hasMany(StockReceipt::class, 'created_by');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'created_by');
    }

    public function requestedStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'requested_by');
    }

    public function reviewedStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'reviewed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function createdExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function approvedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'changed_by');
    }

    public function requestedSaleVoids(): HasMany
    {
        return $this->hasMany(SaleVoid::class, 'requested_by');
    }

    public function reviewedSaleVoids(): HasMany
    {
        return $this->hasMany(SaleVoid::class, 'reviewed_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->hasAnyRole([$role]);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $role = $this->relationLoaded('role')
            ? $this->getRelation('role')
            : $this->role()->first();

        if ($role === null) {
            return false;
        }

        $normalizedRoles = array_map(
            static fn (string $roleSlug): string => mb_strtolower(trim($roleSlug)),
            $roles,
        );

        return in_array(mb_strtolower($role->slug), $normalizedRoles, true);
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isCashier(): bool
    {
        return $this->hasRole('cashier');
    }

    public function scopeAccessibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isOwner()) {
            return $query;
        }

        if ($viewer->isAdmin()) {
            if ($viewer->branch_id === null) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->where($query->getModel()->qualifyColumn('branch_id'), $viewer->branch_id)
                ->whereHas('role', fn (Builder $roleQuery): Builder => $roleQuery->where('slug', '!=', 'owner'));
        }

        if ($viewer->isCashier()) {
            return $query->whereKey($viewer->getKey());
        }

        return $query->whereRaw('1 = 0');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
