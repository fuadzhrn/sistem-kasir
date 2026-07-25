<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\CashierPageRequest;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\Cashier\CashierContextService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function __construct(
        private readonly CashierContextService $context,
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
            'maximumDiscount' => $this->maximumDiscount(),
        ]);
    }

    private function maximumDiscount(): string
    {
        $value = Setting::query()
            ->where('key', 'maximum_cashier_discount')
            ->value('value');

        if (! is_numeric($value) || (float) $value < 0) {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
