<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryStatusRequest;
use App\Models\Category;
use App\Services\MasterData\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $categories = Category::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(fn ($subquery) => $subquery
                    ->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $summary = [
            'total' => Category::query()->count(),
            'active' => Category::query()->where('is_active', true)->count(),
            'inactive' => Category::query()->where('is_active', false)->count(),
        ];

        return view('pages.categories.index', compact('categories', 'search', 'status', 'summary'));
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('pages.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);
        $category = $this->service->create($request->validated());

        return redirect()->route('categories.show', $category)->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function show(Category $category): View
    {
        Gate::authorize('view', $category);
        $category->loadCount('products');

        return view('pages.categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('pages.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);
        $category = $this->service->update($category, $request->validated());

        return redirect()->route('categories.show', $category)->with('status', 'Kategori berhasil diperbarui.');
    }

    public function updateStatus(UpdateCategoryStatusRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('updateStatus', $category);
        $category = $this->service->updateStatus($category, $request->boolean('is_active'));

        return back()->with('status', $category->is_active
            ? 'Kategori berhasil diaktifkan.'
            : 'Kategori berhasil dinonaktifkan. Produk lama tetap terhubung.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);
        $this->service->deleteIfUnused($category);

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil dihapus.');
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
