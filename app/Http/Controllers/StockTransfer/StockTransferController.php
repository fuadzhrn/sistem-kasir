<?php

namespace App\Http\Controllers\StockTransfer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockTransfer\CancelStockTransferRequest;
use App\Http\Requests\StockTransfer\CompleteStockTransferRequest;
use App\Http\Requests\StockTransfer\RejectStockTransferRequest;
use App\Http\Requests\StockTransfer\StockTransferIndexRequest;
use App\Http\Requests\StockTransfer\StoreStockTransferRequest;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\Authorization\BranchAccessService;
use App\Services\StockTransfer\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $service,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function index(StockTransferIndexRequest $request): View
    {
        Gate::authorize('viewAny', StockTransfer::class);
        $viewer = $request->user();
        $filters = $request->validated();
        $query = StockTransfer::query()
            ->select([
                'id', 'transfer_number', 'from_branch_id', 'to_branch_id', 'product_id',
                'quantity', 'status', 'requested_by', 'reviewed_by', 'created_at',
            ])
            ->accessibleTo($viewer)
            ->with([
                'sourceBranch:id,code,name',
                'destinationBranch:id,code,name',
                'product:id,code,name,brand,size',
                'requester:id,name',
                'reviewer:id,name',
            ]);

        if ($viewer->isOwner() && isset($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
            $query->where(function ($nested) use ($branchId): void {
                $nested->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
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
                    ->where('transfer_number', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('product', function ($productQuery) use ($filters): void {
                        $productQuery
                            ->where('code', 'like', '%'.$filters['search'].'%')
                            ->orWhere('name', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        $summaryQuery = clone $query;

        return view('pages.stock-transfers.index', [
            'transfers' => $query->latest('created_at')->latest('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'labels' => StockTransfer::labels(),
            'branches' => $viewer->isOwner()
                ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name'])
                : collect(),
            'products' => Product::query()->orderBy('name')->get(['id', 'code', 'name']),
            'summary' => [
                'documents' => (clone $summaryQuery)->count(),
                'pending' => (clone $summaryQuery)->where('status', StockTransfer::STATUS_PENDING)->count(),
                'completed' => (clone $summaryQuery)->where('status', StockTransfer::STATUS_COMPLETED)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', StockTransfer::class);
        $viewer = $request->user();
        $source = null;

        if ($viewer->isAdmin()) {
            $sourceId = $this->branchAccess->resolveBranchId($viewer);
            $source = Branch::query()->whereKey($sourceId)->firstOrFail(['id', 'code', 'name']);
        }

        $sourceIds = $viewer->isOwner()
            ? Branch::query()->where('is_active', true)->pluck('id')
            : collect([$source->id]);
        $stockQuantities = BranchStock::query()
            ->whereIn('branch_id', $sourceIds)
            ->get(['branch_id', 'product_id', 'quantity'])
            ->mapWithKeys(fn (BranchStock $stock): array => [
                $stock->branch_id.':'.$stock->product_id => (string) $stock->quantity,
            ]);

        return view('pages.stock-transfers.create', [
            'source' => $source,
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'products' => Product::query()
                ->where('is_active', true)
                ->with('unit:id,name,symbol')
                ->orderBy('name')
                ->get(['id', 'unit_id', 'code', 'name', 'brand', 'size']),
            'stockQuantities' => $stockQuantities,
        ]);
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        Gate::authorize('create', StockTransfer::class);
        $validated = $request->validated();
        $viewer = $request->user();
        $sourceId = $this->branchAccess->resolveBranchId(
            $viewer,
            $viewer->isOwner() ? (int) $validated['from_branch_id'] : null,
        );
        $source = Branch::query()->whereKey($sourceId)->where('is_active', true)->firstOrFail();
        $destination = Branch::query()
            ->whereKey((int) $validated['to_branch_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $product = Product::query()
            ->whereKey((int) $validated['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $transfer = $this->service->request(
                $source,
                $destination,
                $product,
                $validated['quantity'],
                $validated['notes'],
                $viewer,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Permintaan mutasi gagal disimpan. Tidak ada stok yang berubah.');
        }

        return redirect()
            ->route('stock-transfers.show', $transfer)
            ->with('success', 'Permintaan mutasi berhasil dibuat dan menunggu persetujuan Owner.');
    }

    public function show(Request $request, StockTransfer $stockTransfer): View
    {
        Gate::authorize('view', $stockTransfer);
        $viewer = $request->user();

        if ($viewer->isAdmin()) {
            $stockTransfer = StockTransfer::query()
                ->select([
                    'id', 'transfer_number', 'from_branch_id', 'to_branch_id', 'product_id',
                    'quantity', 'status', 'source_quantity_before', 'source_quantity_after',
                    'destination_quantity_before', 'destination_quantity_after', 'notes',
                    'requested_by', 'reviewed_by', 'reviewed_at', 'rejection_reason',
                    'completed_at', 'created_at',
                ])
                ->findOrFail($stockTransfer->getKey());
        }

        $stockTransfer->load([
            'sourceBranch:id,code,name',
            'destinationBranch:id,code,name',
            'product' => fn ($query) => $query
                ->select(['id', 'unit_id', 'code', 'name', 'brand', 'size'])
                ->with('unit:id,name,symbol'),
            'requester:id,name',
            'reviewer:id,name',
        ]);

        return view('pages.stock-transfers.show', compact('stockTransfer'));
    }

    public function complete(
        CompleteStockTransferRequest $request,
        StockTransfer $stockTransfer,
    ): RedirectResponse {
        Gate::authorize('complete', $stockTransfer);

        try {
            $this->service->complete($stockTransfer, $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Penyelesaian mutasi gagal dan seluruh perubahan dibatalkan.');
        }

        return back()->with('success', 'Mutasi selesai. Stok kedua cabang telah diperbarui.');
    }

    public function reject(
        RejectStockTransferRequest $request,
        StockTransfer $stockTransfer,
    ): RedirectResponse {
        Gate::authorize('reject', $stockTransfer);
        $this->service->reject(
            $stockTransfer,
            $request->validated('rejection_reason'),
            $request->user(),
        );

        return back()->with('success', 'Permintaan mutasi telah ditolak tanpa mengubah stok.');
    }

    public function cancel(
        CancelStockTransferRequest $request,
        StockTransfer $stockTransfer,
    ): RedirectResponse {
        Gate::authorize('cancel', $stockTransfer);
        $this->service->cancel(
            $stockTransfer,
            $request->validated('cancellation_reason'),
            $request->user(),
        );

        return back()->with('success', 'Permintaan mutasi telah dibatalkan tanpa mengubah stok.');
    }
}
