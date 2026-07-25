<?php

namespace App\Http\Controllers\StockAdjustment;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustment\StockAdjustmentIndexRequest;
use App\Http\Requests\StockAdjustment\StoreStockAdjustmentRequest;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;
use App\Services\StockAdjustment\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentService $service,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function index(StockAdjustmentIndexRequest $request): View
    {
        Gate::authorize('viewAny', StockAdjustment::class);
        $viewer = $request->user();
        $filters = $request->validated();
        $query = StockAdjustment::query()
            ->select([
                'id', 'adjustment_number', 'branch_id', 'product_id', 'adjustment_type',
                'quantity_before', 'quantity_change', 'quantity_after', 'reason',
                'created_by', 'created_at',
            ])
            ->accessibleTo($viewer)
            ->with([
                'branch:id,code,name',
                'product:id,code,name,brand,size',
                'creator:id,name',
            ]);

        if ($viewer->isOwner() && isset($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (isset($filters['adjustment_type'])) {
            $query->where('adjustment_type', $filters['adjustment_type']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('created_by', (int) $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($nested) use ($filters): void {
                $nested
                    ->where('adjustment_number', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('product', function ($productQuery) use ($filters): void {
                        $productQuery
                            ->where('code', 'like', '%'.$filters['search'].'%')
                            ->orWhere('name', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        $summaryQuery = clone $query;

        return view('pages.stock-adjustments.index', [
            'adjustments' => $query->latest('created_at')->latest('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'labels' => StockAdjustment::labels(),
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'products' => Product::query()->orderBy('name')->get(['id', 'code', 'name']),
            'users' => User::query()
                ->accessibleTo($viewer)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'summary' => [
                'documents' => (clone $summaryQuery)->count(),
                'increases' => (clone $summaryQuery)->where('quantity_change', '>', 0)->count(),
                'decreases' => (clone $summaryQuery)->where('quantity_change', '<', 0)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', StockAdjustment::class);
        $viewer = $request->user();
        $branch = null;

        if ($viewer->isAdmin()) {
            $branchId = $this->branchAccess->resolveBranchId($viewer);
            $branch = Branch::query()->whereKey($branchId)->where('is_active', true)
                ->firstOrFail(['id', 'code', 'name']);
        }

        $branchIds = $viewer->isOwner()
            ? Branch::query()->where('is_active', true)->pluck('id')
            : collect([$branch->id]);
        $stockQuantities = BranchStock::query()
            ->whereIn('branch_id', $branchIds)
            ->get(['branch_id', 'product_id', 'quantity'])
            ->mapWithKeys(fn (BranchStock $stock): array => [
                $stock->branch_id.':'.$stock->product_id => (string) $stock->quantity,
            ]);

        return view('pages.stock-adjustments.create', [
            'branch' => $branch,
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'products' => Product::query()
                ->where('is_active', true)
                ->with('unit:id,name,symbol')
                ->orderBy('name')
                ->get(['id', 'unit_id', 'code', 'name', 'brand', 'size']),
            'labels' => StockAdjustment::labels(),
            'stockQuantities' => $stockQuantities,
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', StockAdjustment::class);
        $validated = $request->validated();
        $viewer = $request->user();
        $branchId = $this->branchAccess->resolveBranchId(
            $viewer,
            $viewer->isOwner() ? (int) $validated['branch_id'] : null,
        );
        $branch = Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();
        $product = Product::query()
            ->whereKey((int) $validated['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $adjustment = $this->service->create(
                $branch,
                $product,
                $validated['adjustment_type'],
                $validated['quantity'] ?? null,
                $validated['target_quantity'] ?? null,
                $validated['reason'],
                $viewer,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Penyimpanan gagal dan seluruh perubahan stok telah dibatalkan.');
        }

        return redirect()
            ->route('stock-adjustments.show', $adjustment)
            ->with('success', 'Penyesuaian stok berhasil disimpan dan pergerakan stok telah dicatat.');
    }

    public function show(Request $request, StockAdjustment $stockAdjustment): View
    {
        Gate::authorize('view', $stockAdjustment);
        $viewer = $request->user();

        if ($viewer->isAdmin()) {
            $stockAdjustment = StockAdjustment::query()
                ->select([
                    'id', 'adjustment_number', 'branch_id', 'product_id', 'adjustment_type',
                    'quantity', 'target_quantity', 'quantity_before', 'quantity_change',
                    'quantity_after', 'reason', 'created_by', 'created_at',
                ])
                ->findOrFail($stockAdjustment->getKey());
        }

        $stockAdjustment->load([
            'branch:id,code,name',
            'product' => fn ($query) => $query
                ->select(['id', 'unit_id', 'code', 'name', 'brand', 'size', 'is_active'])
                ->with('unit:id,name,symbol'),
            'creator:id,name',
        ]);

        return view('pages.stock-adjustments.show', [
            'stockAdjustment' => $stockAdjustment,
        ]);
    }
}
