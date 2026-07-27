<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Observers\BusinessActivityObserver;
use App\Policies\ActivityLogPolicy;
use App\Policies\BranchPolicy;
use App\Policies\BranchStockPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use App\Policies\StoreSettingPolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
use App\Services\Authorization\BranchAccessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Branch::class,
            Category::class,
            Unit::class,
            PaymentMethod::class,
            Product::class,
            User::class,
            StockMovement::class,
            StockAdjustment::class,
            StockTransfer::class,
        ] as $model) {
            $model::observe(BusinessActivityObserver::class);
        }

        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(BranchStock::class, BranchStockPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Setting::class, StoreSettingPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);

        Gate::define(
            'view-profit',
            fn (User $user, Branch $branch): bool => $user->is_active
                && $user->hasAnyRole(['owner', 'admin'])
                && app(BranchAccessService::class)->canAccessBranch($user, $branch),
        );
        Gate::define(
            'view-global-report',
            fn (User $user): bool => $user->is_active && $user->isOwner(),
        );
        Gate::define(
            'manage-branches',
            fn (User $user): bool => $user->is_active && $user->isOwner(),
        );
        Gate::define(
            'manage-users',
            fn (User $user): bool => $user->is_active && $user->isOwner(),
        );
        Gate::define(
            'manage-settings',
            fn (User $user): bool => $user->is_active && $user->isOwner(),
        );
        Gate::define(
            'view-activity-logs',
            fn (User $user): bool => $user->is_active
                && ($user->isOwner() || ($user->isAdmin() && $user->branch_id !== null)),
        );
    }
}
