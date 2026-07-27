<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\DeleteProductImageRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UpdateProductStatusRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\Product\ProductService;
use App\Services\Setting\StoreSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service,
        private readonly StoreSettingService $settings,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['nullable', 'integer', 'exists:units,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $viewer = $request->user();
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryFilter = isset($filters['category']) ? (int) $filters['category'] : null;
        $unitFilter = isset($filters['unit']) ? (int) $filters['unit'] : null;
        $status = $filters['status'] ?? null;
        $columns = [
            'id', 'category_id', 'unit_id', 'code', 'barcode', 'name', 'brand', 'size',
            'selling_price', 'minimum_stock', 'image_path', 'is_active', 'updated_at',
        ];

        if ($viewer->isOwner()) {
            $columns[] = 'purchase_price';
        }

        $products = Product::query()
            ->select($columns)
            ->with(['category:id,name,slug,is_active', 'unit:id,name,symbol,slug,is_active'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(fn ($subquery) => $subquery
                    ->where('code', 'like', $term)
                    ->orWhere('barcode', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('brand', 'like', $term)
                    ->orWhere('size', 'like', $term));
            })
            ->when($categoryFilter !== null, fn ($query) => $query->where('category_id', $categoryFilter))
            ->when($unitFilter !== null, fn ($query) => $query->where('unit_id', $unitFilter))
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $units = Unit::query()->orderBy('name')->get(['id', 'name', 'symbol']);
        $summary = [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('is_active', true)->count(),
            'inactive' => Product::query()->where('is_active', false)->count(),
        ];

        return view('pages.products.index', compact(
            'products',
            'categories',
            'units',
            'search',
            'categoryFilter',
            'unitFilter',
            'status',
            'summary',
        ));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Product::class);
        [$categories, $units] = $this->activeMasterData();
        $isOwner = $request->user()->isOwner();
        $defaultMinimumStock = $this->settings->defaultMinimumStock();

        return view('pages.products.create', compact(
            'categories',
            'units',
            'isOwner',
            'defaultMinimumStock',
        ));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);
        $product = $this->service->create(
            $request->validated(),
            $request->user(),
            $request->file('image'),
        );

        return redirect()->route('products.show', $product)->with('status', 'Produk berhasil ditambahkan.');
    }

    public function show(Request $request, Product $product): View
    {
        Gate::authorize('view', $product);
        $product->load([
            'category:id,name,slug,is_active',
            'unit:id,name,symbol,slug,is_active',
            'creator:id,name',
            'updater:id,name',
        ]);
        $isOwner = $request->user()->isOwner();

        if (! $isOwner) {
            $product->makeHidden('purchase_price');
        }

        return view('pages.products.show', compact('product', 'isOwner'));
    }

    public function edit(Request $request, Product $product): View
    {
        Gate::authorize('update', $product);
        $categories = Category::query()
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhere('id', $product->category_id))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
        $units = Unit::query()
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhere('id', $product->unit_id))
            ->orderBy('name')
            ->get(['id', 'name', 'symbol', 'is_active']);
        $isOwner = $request->user()->isOwner();

        if (! $isOwner) {
            $product->makeHidden('purchase_price');
        }

        return view('pages.products.edit', compact('product', 'categories', 'units', 'isOwner'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);
        $product = $this->service->update(
            $product,
            $request->validated(),
            $request->user(),
            $request->file('image'),
        );

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Produk berhasil diperbarui. Perubahan harga, jika ada, telah dicatat.');
    }

    public function updateStatus(
        UpdateProductStatusRequest $request,
        Product $product,
    ): RedirectResponse {
        Gate::authorize('updateStatus', $product);
        $product = $this->service->updateStatus(
            $product,
            $request->boolean('is_active'),
            $request->user(),
        );

        return back()->with(
            'status',
            $product->is_active ? 'Produk berhasil diaktifkan.' : 'Produk berhasil dinonaktifkan.',
        );
    }

    public function destroyImage(DeleteProductImageRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('removeImage', $product);
        $this->service->removeImage($product, $request->user());

        return back()->with('status', 'Foto produk berhasil dihapus.');
    }

    public function priceHistory(Request $request, Product $product): View
    {
        Gate::authorize('viewPriceHistory', $product);
        $isOwner = $request->user()->isOwner();
        $columns = [
            'id', 'product_id', 'changed_by', 'old_selling_price', 'new_selling_price',
            'reason', 'changed_at', 'created_at',
        ];

        if ($isOwner) {
            array_push($columns, 'old_purchase_price', 'new_purchase_price');
        }

        $priceHistories = $product->priceHistories()
            ->select($columns)
            ->with('changedBy:id,name')
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        if (! $isOwner) {
            $priceHistories->getCollection()->each(
                fn ($history) => $history->makeHidden(['old_purchase_price', 'new_purchase_price']),
            );
            $product->makeHidden('purchase_price');
        }

        return view('pages.products.price-history', compact('product', 'priceHistories', 'isOwner'));
    }

    private function activeMasterData(): array
    {
        return [
            Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            Unit::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'symbol']),
        ];
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
