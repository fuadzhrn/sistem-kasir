<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseCategoryRequest;
use App\Http\Requests\Expense\UpdateExpenseCategoryRequest;
use App\Http\Requests\Expense\UpdateExpenseCategoryStatusRequest;
use App\Models\ExpenseCategory;
use App\Services\Expense\ExpenseCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        private readonly ExpenseCategoryService $service,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ExpenseCategory::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($status, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $summary = [
            'total' => ExpenseCategory::query()->count(),
            'active' => ExpenseCategory::query()->where('is_active', true)->count(),
            'inactive' => ExpenseCategory::query()->where('is_active', false)->count(),
        ];

        return view('pages.expense-categories.index', compact(
            'categories',
            'search',
            'status',
            'summary',
        ));
    }

    public function create(): View
    {
        Gate::authorize('create', ExpenseCategory::class);

        return view('pages.expense-categories.create');
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $category = $this->service->create(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expense-categories.edit', $category)
            ->with('status', 'Kategori pengeluaran berhasil ditambahkan.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        Gate::authorize('update', $expenseCategory);

        return view('pages.expense-categories.edit', compact('expenseCategory'));
    }

    public function update(
        UpdateExpenseCategoryRequest $request,
        ExpenseCategory $expenseCategory,
    ): RedirectResponse {
        $this->service->update(
            $expenseCategory,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expense-categories.index')
            ->with('status', 'Kategori pengeluaran berhasil diperbarui.');
    }

    public function updateStatus(
        UpdateExpenseCategoryStatusRequest $request,
        ExpenseCategory $expenseCategory,
    ): RedirectResponse {
        $category = $this->service->updateStatus(
            $expenseCategory,
            $request->boolean('is_active'),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return back()->with('status', $category->is_active
            ? 'Kategori pengeluaran berhasil diaktifkan.'
            : 'Kategori pengeluaran dinonaktifkan. Histori tetap tersimpan.');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        Gate::authorize('delete', $expenseCategory);
        $this->service->deleteIfUnused(
            $expenseCategory,
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expense-categories.index')
            ->with('status', 'Kategori pengeluaran berhasil dihapus.');
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
