<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\SaleHistoryRequest;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SaleHistoryController extends Controller
{
    /**
     * Columns safe for every role, including Cashier.
     *
     * @var array<int, string>
     */
    private const SAFE_SALE_COLUMNS = [
        'id',
        'branch_id',
        'cashier_id',
        'payment_method_id',
        'invoice_number',
        'transaction_date',
        'subtotal',
        'discount_amount',
        'total',
        'amount_paid',
        'change_amount',
        'payment_method_name',
        'status',
        'notes',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    private const SAFE_ITEM_COLUMNS = [
        'id',
        'sale_id',
        'product_code',
        'product_name',
        'unit_name',
        'product_size',
        'quantity',
        'selling_price',
        'discount_amount',
        'subtotal',
    ];

    public function index(SaleHistoryRequest $request): View
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('viewAny', Sale::class);
        $filters = $request->validated();
        $query = $this->filteredQuery($user, $filters);

        $summary = $this->summary(clone $query);
        $sales = (clone $query)
            ->select(self::SAFE_SALE_COLUMNS)
            ->with([
                'branch:id,code,name',
                'cashier:id,name',
                'paymentMethod:id,name',
            ])
            ->withCount('items')
            ->when(
                isset($filters['search']),
                fn (Builder $salesQuery): Builder => $salesQuery->orderByRaw(
                    'CASE WHEN invoice_number = ? THEN 0 WHEN invoice_number LIKE ? THEN 1 ELSE 2 END',
                    [$filters['search'], $filters['search'].'%'],
                ),
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        return view('pages.sales.index', [
            'sales' => $sales,
            'summary' => $summary,
            'filters' => $filters,
            'branches' => $this->branchOptions($user),
            'cashiers' => $this->cashierOptions($user, $filters),
            'paymentMethods' => $this->paymentMethodOptions($user),
        ]);
    }

    public function show(Request $request, Sale $sale): View
    {
        $user = $request->user();
        $showInternal = $user->hasAnyRole(['owner', 'admin']);
        $sale = $this->accessibleSale($user, $sale->getKey(), $showInternal);
        Gate::forUser($user)->authorize('view', $sale);

        return view('pages.sales.show', [
            'sale' => $sale,
            'showInternal' => $showInternal,
        ]);
    }

    public function receipt(Request $request, Sale $sale): View
    {
        $user = $request->user();
        $sale = $this->accessibleSale($user, $sale->getKey(), false);
        Gate::forUser($user)->authorize('print', $sale);

        return view('pages.sales.receipt-preview', ['sale' => $sale]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(User $user, array $filters): Builder
    {
        return Sale::query()
            ->accessibleTo($user)
            ->when(isset($filters['branch_id']) && $user->isOwner(), fn (Builder $query) => $query
                ->where('branch_id', $filters['branch_id']))
            ->when(isset($filters['cashier_id']) && ! $user->isCashier(), fn (Builder $query) => $query
                ->where('cashier_id', $filters['cashier_id']))
            ->when(isset($filters['status']), fn (Builder $query) => $query
                ->where('status', $filters['status']))
            ->when(isset($filters['payment_method_id']), fn (Builder $query) => $query
                ->where('payment_method_id', $filters['payment_method_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query
                ->where('transaction_date', '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay()))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query
                ->where('transaction_date', '<', CarbonImmutable::parse($filters['date_to'])->addDay()->startOfDay()))
            ->when(isset($filters['search']), function (Builder $query) use ($filters, $user): void {
                $search = $filters['search'];

                $query->where(function (Builder $searchQuery) use ($search, $user): void {
                    $searchQuery
                        ->where('invoice_number', 'like', '%'.$search.'%')
                        ->orWhereHas('cashier', fn (Builder $cashierQuery) => $cashierQuery
                            ->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('paymentMethod', fn (Builder $paymentQuery) => $paymentQuery
                            ->where('name', 'like', '%'.$search.'%'));

                    if ($user->isOwner()) {
                        $searchQuery->orWhereHas('branch', fn (Builder $branchQuery) => $branchQuery
                            ->where('name', 'like', '%'.$search.'%'));
                    }
                });
            });
    }

    /**
     * @return array<string, string|int>
     */
    private function summary(Builder $query): array
    {
        $result = $query
            ->reorder()
            ->selectRaw(
                'COUNT(*) AS transaction_count,
                COALESCE(SUM(total), 0) AS net_total,
                COALESCE(SUM(discount_amount), 0) AS discount_total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS void_requested_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS voided_count',
                [
                    Sale::STATUS_COMPLETED,
                    Sale::STATUS_VOID_REQUESTED,
                    Sale::STATUS_VOIDED,
                ],
            )
            ->first();

        return [
            'transaction_count' => (int) ($result?->transaction_count ?? 0),
            'net_total' => (string) ($result?->net_total ?? '0.00'),
            'discount_total' => (string) ($result?->discount_total ?? '0.00'),
            'completed_count' => (int) ($result?->completed_count ?? 0),
            'void_requested_count' => (int) ($result?->void_requested_count ?? 0),
            'voided_count' => (int) ($result?->voided_count ?? 0),
        ];
    }

    private function accessibleSale(User $user, int|string $saleId, bool $includeInternal): Sale
    {
        $query = Sale::query()
            ->accessibleTo($user)
            ->whereKey($saleId)
            ->with([
                'branch:id,code,name,address,phone',
                'cashier:id,name',
                'paymentMethod:id,name',
            ]);

        if (! $includeInternal) {
            $query
                ->select(self::SAFE_SALE_COLUMNS)
                ->with(['items' => fn ($items) => $items
                    ->select(self::SAFE_ITEM_COLUMNS)
                    ->orderBy('id')]);
        } else {
            $query->with(['items' => fn ($items) => $items->orderBy('id')]);
        }

        return $query->firstOrFail();
    }

    /**
     * @return Collection<int, Branch>
     */
    private function branchOptions(User $user)
    {
        if (! $user->isOwner()) {
            return new Collection;
        }

        return Branch::query()
            ->whereHas('sales')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, User>
     */
    private function cashierOptions(User $user, array $filters)
    {
        if ($user->isCashier()) {
            return new Collection;
        }

        return User::query()
            ->whereHas('sales', function (Builder $salesQuery) use ($user, $filters): void {
                $salesQuery
                    ->accessibleTo($user)
                    ->when(
                        $user->isOwner() && isset($filters['branch_id']),
                        fn (Builder $query) => $query->where('branch_id', $filters['branch_id']),
                    );
            })
            ->when($user->isAdmin(), fn (Builder $query) => $query
                ->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name']);
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    private function paymentMethodOptions(User $user)
    {
        return PaymentMethod::query()
            ->whereHas('sales', fn (Builder $salesQuery) => $salesQuery->accessibleTo($user))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
