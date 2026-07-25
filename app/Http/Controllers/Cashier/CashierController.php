<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\CashierPageRequest;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cashier\CashierContextService;
use App\Services\Sale\SaleCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class CashierController extends Controller
{
    public function __construct(
        private readonly CashierContextService $context,
        private readonly SaleCalculator $calculator,
    ) {}

    public function index(CashierPageRequest $request): View
    {
        Gate::authorize('create', Sale::class);
        $user = $request->user()->loadMissing(['role', 'branch']);
        $validated = $request->validated();
        $branch = null;

        if (! $user->isOwner() || isset($validated['branch_id'])) {
            $branch = $this->context->resolveBranch(
                $user,
                $user->isOwner() ? (int) $validated['branch_id'] : null,
            );
        }

        return view('pages.cashier.index', [
            'branch' => $branch,
            'branches' => $this->context->availableBranches($user),
            'canSwitchBranch' => $this->context->canSwitchBranch($user),
            'categories' => Category::query()
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query
                    ->where('is_active', true)
                    ->whereHas('unit', fn ($unitQuery) => $unitQuery->where('is_active', true)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'type']),
            'maximumDiscount' => $this->maximumDiscount($user),
            'discountRestricted' => $user->isCashier(),
        ]);
    }

    private function maximumDiscount(User $user): ?string
    {
        if (! $user->isCashier()) {
            return null;
        }

        $value = Setting::query()
            ->where('key', 'maximum_cashier_discount')
            ->value('value');

        try {
            $normalized = $value === null
                ? '0.00'
                : $this->calculator->normalizeMoney((string) $value);
        } catch (InvalidArgumentException) {
            return '0.00';
        }

        return $this->calculator->compareMoney($normalized, '0.00') < 0
            ? '0.00'
            : $normalized;
    }
}
