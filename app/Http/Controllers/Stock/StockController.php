<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StockHistoryRequest;
use App\Http\Requests\Stock\StockIndexRequest;
use App\Http\Requests\Stock\StoreInitialStockRequest;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;
use App\Services\Stock\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly StockService $service,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function index(StockIndexRequest $request): View
    {
        Gate::authorize('viewAny', BranchStock::class);
        $filters = $request->validated();
        $viewer = $request->user();

        if ($viewer->isOwner() && ! isset($filters['branch_id'])) {
            return view('pages.stocks.index', [
                'branchSummaries' => $this->branchSummaries(),
                'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
                'selectedBranch' => null,
                'products' => null,
                'categories' => collect(),
                'filters' => $filters,
                'stockSummary' => null,
            ]);
        }

        $branchId = $this->branchAccess->resolveBranchId(
            $viewer,
            $viewer->isOwner() ? (int) $filters['branch_id'] : null,
        );
        $branch = Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();
        Gate::authorize('createInitial', [BranchStock::class, $branch]);

        $products = $this->stockProductsQuery($branch, $filters)
            ->paginate(20)
            ->withQueryString();

        $products->getCollection()->each(function (Product $product): void {
            $product->setAttribute(
                'stock_status',
                $this->service->calculateStockStatus(
                    (string) $product->stock_quantity,
                    (string) $product->minimum_stock,
                ),
            );
        });

        return view('pages.stocks.index', [
            'branchSummaries' => null,
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'selectedBranch' => $branch,
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'stockSummary' => $this->stockSummaryForBranch($branch),
        ]);
    }

    public function show(Request $request, BranchStock $branchStock): View
    {
        Gate::authorize('view', $branchStock);
        $viewer = $request->user();
        $branchStock->load([
            'branch:id,code,name,is_active',
            'product' => fn ($query) => $query
                ->select([
                    'id', 'category_id', 'unit_id', 'code', 'barcode', 'name', 'brand',
                    'size', 'minimum_stock', 'image_path', 'is_active',
                ])
                ->with(['category:id,name', 'unit:id,name,symbol']),
        ]);

        if ($viewer->isAdmin()) {
            $branchStock->makeHidden('average_cost');
        }

        $movementColumns = [
            'id', 'branch_id', 'product_id', 'created_by', 'movement_type',
            'quantity_before', 'quantity_change', 'quantity_after', 'notes', 'created_at',
        ];

        if ($viewer->isOwner()) {
            $movementColumns[] = 'unit_cost';
        }

        $movements = StockMovement::query()
            ->select($movementColumns)
            ->where('branch_id', $branchStock->branch_id)
            ->where('product_id', $branchStock->product_id)
            ->with('creator:id,name')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return view('pages.stocks.show', [
            'branchStock' => $branchStock,
            'movements' => $movements,
            'stockStatus' => $this->service->calculateStockStatus(
                (string) $branchStock->quantity,
                (string) $branchStock->product->minimum_stock,
            ),
            'canCorrect' => $this->service->canSetInitialStock($branchStock->branch, $branchStock->product),
            'movementLabels' => $this->movementLabels(),
        ]);
    }

    public function createInitial(Request $request): View
    {
        Gate::authorize('viewAny', BranchStock::class);
        $viewer = $request->user();
        $selection = $request->validate([
            'branch_id' => [$viewer->isOwner() ? 'nullable' : 'prohibited', 'integer', 'exists:branches,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ], [
            'branch_id.prohibited' => 'Admin hanya dapat mengelola stok cabang akun.',
            'branch_id.exists' => 'Cabang yang dipilih tidak tersedia.',
            'product_id.exists' => 'Produk yang dipilih tidak tersedia.',
        ]);

        $branch = null;

        if ($viewer->isAdmin() || isset($selection['branch_id'])) {
            $branchId = $this->branchAccess->resolveBranchId(
                $viewer,
                $viewer->isOwner() ? (int) $selection['branch_id'] : null,
            );
            $branch = Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();
            Gate::authorize('createInitial', [BranchStock::class, $branch]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->with('unit:id,name,symbol')
            ->orderBy('name')
            ->get(['id', 'unit_id', 'code', 'name', 'minimum_stock']);
        $product = null;
        $branchStock = null;
        $stockStatus = StockService::STATUS_OUT;
        $canCorrect = true;
        $hasReferenceCost = true;

        if (isset($selection['product_id'])) {
            $columns = [
                'id', 'unit_id', 'code', 'name', 'minimum_stock', 'is_active',
            ];

            if ($viewer->isOwner()) {
                $columns[] = 'purchase_price';
            }

            $product = Product::query()
                ->select($columns)
                ->whereKey((int) $selection['product_id'])
                ->where('is_active', true)
                ->with('unit:id,name,symbol')
                ->firstOrFail();
        }

        if ($branch !== null && $product !== null) {
            $branchStock = BranchStock::query()
                ->select(['id', 'branch_id', 'product_id', 'quantity', 'updated_at'])
                ->where('branch_id', $branch->getKey())
                ->where('product_id', $product->getKey())
                ->first();
            $stockStatus = $this->service->calculateStockStatus(
                (string) ($branchStock?->quantity ?? '0'),
                (string) $product->minimum_stock,
            );
            $canCorrect = $this->service->canSetInitialStock($branch, $product);
            $hasReferenceCost = $viewer->isOwner()
                ? (float) $product->purchase_price > 0
                : true;

            if ($viewer->isAdmin()) {
                $product->makeHidden('purchase_price');
            }
        }

        return view('pages.stocks.initial', [
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'branch' => $branch,
            'products' => $products,
            'product' => $product,
            'branchStock' => $branchStock,
            'stockStatus' => $stockStatus,
            'canCorrect' => $canCorrect,
            'hasReferenceCost' => $hasReferenceCost,
        ]);
    }

    public function storeInitial(StoreInitialStockRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $viewer = $request->user();
        $branchId = $this->branchAccess->resolveBranchId(
            $viewer,
            $viewer->isOwner() ? (int) $validated['branch_id'] : null,
        );
        $branch = Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();
        $product = Product::query()->whereKey((int) $validated['product_id'])->where('is_active', true)->firstOrFail();
        $existingStock = BranchStock::query()
            ->where('branch_id', $branch->getKey())
            ->where('product_id', $product->getKey())
            ->first();

        if ($existingStock === null) {
            Gate::authorize('createInitial', [BranchStock::class, $branch]);
        } else {
            Gate::authorize('updateInitial', $existingStock);
        }

        $wasCorrection = $existingStock !== null
            && StockMovement::query()
                ->where('branch_id', $branch->getKey())
                ->where('product_id', $product->getKey())
                ->where('movement_type', StockMovement::TYPE_INITIAL)
                ->exists();

        $branchStock = $this->service->setInitialStock(
            $branch,
            $product,
            (string) $validated['quantity'],
            (string) $validated['reason'],
            $viewer,
        );

        return redirect()
            ->route('stocks.show', $branchStock)
            ->with('status', $wasCorrection
                ? 'Stok awal berhasil dikoreksi dan riwayat baru telah dicatat.'
                : 'Stok awal berhasil disimpan dan riwayat telah dicatat.');
    }

    public function history(StockHistoryRequest $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);
        $filters = $request->validated();
        $viewer = $request->user();
        $columns = [
            'id', 'branch_id', 'product_id', 'created_by', 'movement_type',
            'quantity_before', 'quantity_change', 'quantity_after', 'notes', 'created_at',
        ];

        if ($viewer->isOwner()) {
            $columns[] = 'unit_cost';
        }

        $movements = StockMovement::query()
            ->select($columns)
            ->accessibleTo($viewer)
            ->with([
                'branch:id,code,name',
                'product' => fn ($query) => $query
                    ->select(['id', 'category_id', 'unit_id', 'code', 'name'])
                    ->with(['category:id,name', 'unit:id,name,symbol']),
                'creator:id,name',
            ])
            ->when($viewer->isOwner() && isset($filters['branch_id']), fn (Builder $query) => $query
                ->where('branch_id', (int) $filters['branch_id']))
            ->when(isset($filters['product_id']), fn (Builder $query) => $query
                ->where('product_id', (int) $filters['product_id']))
            ->when(isset($filters['category_id']), fn (Builder $query) => $query
                ->whereHas('product', fn (Builder $productQuery) => $productQuery
                    ->where('category_id', (int) $filters['category_id'])))
            ->when(isset($filters['movement_type']), fn (Builder $query) => $query
                ->where('movement_type', $filters['movement_type']))
            ->when(isset($filters['user_id']), fn (Builder $query) => $query
                ->where('created_by', (int) $filters['user_id']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query
                ->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query
                ->whereDate('created_at', '<=', $filters['date_to']))
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function (Builder $query) use ($filters): void {
                $term = $this->likeTerm(trim((string) $filters['search']));
                $query->whereHas('product', fn (Builder $productQuery) => $productQuery
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages.stocks.history', [
            'movements' => $movements,
            'filters' => $filters,
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => $this->historyUsers($viewer),
            'movementLabels' => $this->movementLabels(),
        ]);
    }

    private function branchSummaries(): Collection
    {
        $activeProductCount = Product::query()->where('is_active', true)->count();
        $stockStats = BranchStock::query()
            ->join('products', function ($join): void {
                $join->on('products.id', '=', 'branch_stocks.product_id')
                    ->where('products.is_active', true);
            })
            ->selectRaw('branch_stocks.branch_id')
            ->selectRaw('SUM(CASE WHEN branch_stocks.quantity > products.minimum_stock THEN 1 ELSE 0 END) as safe_count')
            ->selectRaw('SUM(CASE WHEN branch_stocks.quantity > 0 AND branch_stocks.quantity <= products.minimum_stock THEN 1 ELSE 0 END) as low_count')
            ->selectRaw('MAX(branch_stocks.updated_at) as last_updated_at')
            ->groupBy('branch_stocks.branch_id')
            ->get()
            ->keyBy('branch_id');

        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(function (Branch $branch) use ($activeProductCount, $stockStats): Branch {
                $stats = $stockStats->get($branch->getKey());
                $safe = (int) ($stats?->safe_count ?? 0);
                $low = (int) ($stats?->low_count ?? 0);
                $branch->setAttribute('active_sku_count', $activeProductCount);
                $branch->setAttribute('safe_count', $safe);
                $branch->setAttribute('low_count', $low);
                $branch->setAttribute('out_count', max(0, $activeProductCount - $safe - $low));
                $branch->setAttribute('last_stock_update', $stats?->last_updated_at);

                return $branch;
            });
    }

    private function stockProductsQuery(Branch $branch, array $filters): Builder
    {
        $query = Product::query()
            ->select([
                'products.id', 'products.category_id', 'products.unit_id', 'products.code',
                'products.barcode', 'products.name', 'products.brand', 'products.size',
                'products.minimum_stock', 'products.image_path', 'products.updated_at',
                'selected_stock.id as branch_stock_id',
                'selected_stock.quantity as stock_quantity',
                'selected_stock.updated_at as stock_updated_at',
            ])
            ->leftJoin('branch_stocks as selected_stock', function ($join) use ($branch): void {
                $join->on('selected_stock.product_id', '=', 'products.id')
                    ->where('selected_stock.branch_id', $branch->getKey());
            })
            ->with(['category:id,name', 'unit:id,name,symbol'])
            ->where('products.is_active', true)
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function (Builder $builder) use ($filters): void {
                $term = $this->likeTerm(trim((string) $filters['search']));
                $builder->where(fn (Builder $searchQuery) => $searchQuery
                    ->where('products.code', 'like', $term)
                    ->orWhere('products.barcode', 'like', $term)
                    ->orWhere('products.name', 'like', $term));
            })
            ->when(isset($filters['category_id']), fn (Builder $builder) => $builder
                ->where('products.category_id', (int) $filters['category_id']));

        match ($filters['status'] ?? null) {
            StockService::STATUS_SAFE => $query->whereColumn('selected_stock.quantity', '>', 'products.minimum_stock'),
            StockService::STATUS_LOW => $query
                ->where('selected_stock.quantity', '>', 0)
                ->whereColumn('selected_stock.quantity', '<=', 'products.minimum_stock'),
            StockService::STATUS_OUT => $query->where(fn (Builder $statusQuery) => $statusQuery
                ->whereNull('selected_stock.id')
                ->orWhere('selected_stock.quantity', '<=', 0)),
            default => null,
        };

        return $query->orderBy('products.name')->orderBy('products.id');
    }

    private function stockSummaryForBranch(Branch $branch): array
    {
        $activeProductCount = Product::query()->where('is_active', true)->count();
        $stats = BranchStock::query()
            ->join('products', function ($join): void {
                $join->on('products.id', '=', 'branch_stocks.product_id')
                    ->where('products.is_active', true);
            })
            ->where('branch_stocks.branch_id', $branch->getKey())
            ->selectRaw('SUM(CASE WHEN branch_stocks.quantity > products.minimum_stock THEN 1 ELSE 0 END) as safe_count')
            ->selectRaw('SUM(CASE WHEN branch_stocks.quantity > 0 AND branch_stocks.quantity <= products.minimum_stock THEN 1 ELSE 0 END) as low_count')
            ->first();
        $safe = (int) ($stats?->safe_count ?? 0);
        $low = (int) ($stats?->low_count ?? 0);

        return [
            'total' => $activeProductCount,
            'safe' => $safe,
            'low' => $low,
            'out' => max(0, $activeProductCount - $safe - $low),
        ];
    }

    private function historyUsers(User $viewer): Collection
    {
        return User::query()
            ->select(['id', 'name'])
            ->when($viewer->isAdmin(), fn (Builder $query) => $query->where('branch_id', $viewer->branch_id))
            ->orderBy('name')
            ->get();
    }

    private function movementLabels(): array
    {
        return [
            StockMovement::TYPE_INITIAL => 'Stok Awal',
            StockMovement::TYPE_PURCHASE => 'Barang Masuk',
            StockMovement::TYPE_SALE => 'Penjualan',
            StockMovement::TYPE_ADJUSTMENT_IN => 'Penyesuaian Masuk',
            StockMovement::TYPE_ADJUSTMENT_OUT => 'Penyesuaian Keluar',
            StockMovement::TYPE_TRANSFER_IN => 'Mutasi Masuk',
            StockMovement::TYPE_TRANSFER_OUT => 'Mutasi Keluar',
            StockMovement::TYPE_VOID_SALE => 'Pembatalan Penjualan',
        ];
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
