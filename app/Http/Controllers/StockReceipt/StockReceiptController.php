<?php

namespace App\Http\Controllers\StockReceipt;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockReceipt\StockReceiptIndexRequest;
use App\Http\Requests\StockReceipt\StoreStockReceiptRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Services\Authorization\BranchAccessService;
use App\Services\StockReceipt\StockReceiptService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StockReceiptController extends Controller
{
    public function __construct(
        private readonly StockReceiptService $service,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function index(StockReceiptIndexRequest $request): View
    {
        Gate::authorize('viewAny', StockReceipt::class);
        $viewer = $request->user();
        $filters = $request->validated();
        $query = StockReceipt::query()
            ->accessibleTo($viewer)
            ->with(['branch:id,code,name', 'creator:id,name'])
            ->withCount('items');

        if ($viewer->isOwner() && isset($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('receipt_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('receipt_date', '<=', $filters['date_to']);
        }

        if (isset($filters['supplier'])) {
            $query->where('supplier_name', 'like', '%'.$filters['supplier'].'%');
        }

        if (isset($filters['search'])) {
            $query->where(function ($nested) use ($filters): void {
                $nested
                    ->where('receipt_number', 'like', '%'.$filters['search'].'%')
                    ->orWhere('supplier_name', 'like', '%'.$filters['search'].'%');
            });
        }

        $summaryQuery = clone $query;
        $receipts = $query
            ->latest('receipt_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages.stock-receipts.index', [
            'receipts' => $receipts,
            'filters' => $filters,
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'summary' => [
                'documents' => (clone $summaryQuery)->count(),
                'products' => StockReceiptItem::query()
                    ->whereIn('stock_receipt_id', (clone $summaryQuery)->select('stock_receipts.id'))
                    ->count(),
                'total_cost' => (string) (clone $summaryQuery)->sum('total_cost'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', StockReceipt::class);
        $viewer = $request->user();
        $branch = null;

        if ($viewer->isAdmin()) {
            $branchId = $this->branchAccess->resolveBranchId($viewer);
            $branch = Branch::query()
                ->whereKey($branchId)
                ->where('is_active', true)
                ->firstOrFail(['id', 'code', 'name']);
        }

        return view('pages.stock-receipts.create', [
            'branch' => $branch,
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'products' => Product::query()
                ->where('is_active', true)
                ->with(['category:id,name', 'unit:id,name,symbol'])
                ->orderBy('name')
                ->get(['id', 'category_id', 'unit_id', 'code', 'name', 'brand', 'size']),
        ]);
    }

    public function store(StoreStockReceiptRequest $request): RedirectResponse
    {
        Gate::authorize('create', StockReceipt::class);
        $validated = $request->validated();
        $viewer = $request->user();
        $branchId = $this->branchAccess->resolveBranchId(
            $viewer,
            $viewer->isOwner() ? (int) $validated['branch_id'] : null,
        );
        $branch = Branch::query()->whereKey($branchId)->where('is_active', true)->firstOrFail();

        try {
            $receipt = $this->service->create(
                $branch,
                CarbonImmutable::createFromFormat('Y-m-d', $validated['receipt_date']),
                $validated['supplier_name'] ?? null,
                $validated['notes'] ?? null,
                $validated['items'],
                $viewer,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Penyimpanan gagal dan seluruh perubahan telah dibatalkan.');
        }

        return redirect()
            ->route('stock-receipts.show', $receipt)
            ->with('success', 'Barang masuk berhasil disimpan. Stok produk dan harga modal rata-rata telah diperbarui.');
    }

    public function show(Request $request, StockReceipt $stockReceipt): View
    {
        Gate::authorize('view', $stockReceipt);
        $viewer = $request->user();
        $itemColumns = [
            'id',
            'stock_receipt_id',
            'product_id',
            'quantity',
            'purchase_price',
            'subtotal',
            'quantity_before',
            'quantity_after',
        ];

        if ($viewer->isOwner()) {
            $itemColumns[] = 'average_cost_before';
            $itemColumns[] = 'average_cost_after';
        }

        $stockReceipt->load([
            'branch:id,code,name',
            'creator:id,name',
            'items' => fn ($query) => $query
                ->select($itemColumns)
                ->with([
                    'product' => fn ($productQuery) => $productQuery
                        ->select(['id', 'category_id', 'unit_id', 'code', 'name', 'brand', 'size', 'is_active'])
                        ->with(['category:id,name', 'unit:id,name,symbol']),
                ])
                ->orderBy('id'),
        ]);

        return view('pages.stock-receipts.show', [
            'stockReceipt' => $stockReceipt,
        ]);
    }
}
