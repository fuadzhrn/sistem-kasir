<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\ApproveExpenseRequest;
use App\Http\Requests\Expense\DeleteExpenseProofRequest;
use App\Http\Requests\Expense\ExpenseIndexRequest;
use App\Http\Requests\Expense\RejectExpenseRequest;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Authorization\BranchAccessService;
use App\Services\Expense\ExpenseApprovalService;
use App\Services\Expense\ExpenseService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $service,
        private readonly ExpenseApprovalService $approvalService,
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function index(ExpenseIndexRequest $request): View
    {
        $filters = $request->validated();
        $user = $request->user();
        $baseQuery = $this->filteredQuery($user, $filters);
        $expenses = (clone $baseQuery)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->with(['branch:id,name', 'expenseCategory:id,name,is_active', 'creator:id,name'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
        $summary = [
            'pending' => (clone $baseQuery)->pending()->count(),
            'approved' => (clone $baseQuery)->approved()->count(),
            'rejected' => (clone $baseQuery)->rejected()->count(),
            'approved_total' => (string) (clone $baseQuery)->approved()->sum('amount'),
            'pending_total' => (string) (clone $baseQuery)->pending()->sum('amount'),
        ];
        $branches = $user->isOwner()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $categories = ExpenseCategory::query()->orderBy('name')->get(['id', 'name']);
        $creators = User::query()
            ->whereHas('createdExpenses', fn (Builder $query): Builder => $query->accessibleTo($user))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.expenses.index', compact(
            'expenses',
            'summary',
            'branches',
            'categories',
            'creators',
            'filters',
        ));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Expense::class);
        $user = $request->user();
        $branches = $user->isOwner()
            ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();
        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.expenses.create', compact('branches', 'categories'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $branchId = $this->branchAccess->resolveBranchId(
            $user,
            $user->isOwner() ? (int) $data['branch_id'] : null,
        );
        $expense = $this->service->create(
            Branch::query()->findOrFail($branchId),
            ExpenseCategory::query()->findOrFail($data['expense_category_id']),
            CarbonImmutable::parse($data['expense_date']),
            (string) $data['amount'],
            $data['description'],
            $request->file('proof'),
            $user,
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Pengeluaran berhasil dicatat dan menunggu persetujuan.');
    }

    public function show(Request $request, int|string $expense): View
    {
        $expense = $this->findExpense($request->user(), $expense);
        Gate::authorize('view', $expense);
        $expense->load([
            'branch:id,name,address',
            'expenseCategory:id,name,is_active',
            'creator:id,name',
            'updater:id,name',
            'approver:id,name',
            'rejector:id,name',
        ]);

        return view('pages.expenses.show', compact('expense'));
    }

    public function edit(Request $request, int|string $expense): View
    {
        $expense = $this->findExpense($request->user(), $expense);
        Gate::authorize('update', $expense);
        $expense->load(['branch:id,name', 'expenseCategory:id,name,is_active']);
        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orWhere('id', $expense->expense_category_id)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return view('pages.expenses.edit', compact('expense', 'categories'));
    }

    public function update(UpdateExpenseRequest $request, int|string $expense): RedirectResponse
    {
        $expense = $this->findExpense($request->user(), $expense);
        $data = $request->validated();
        $expense = $this->service->update(
            $expense,
            ExpenseCategory::query()->findOrFail($data['expense_category_id']),
            CarbonImmutable::parse($data['expense_date']),
            (string) $data['amount'],
            $data['description'],
            $request->file('proof'),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Pengeluaran pending berhasil diperbarui.');
    }

    public function approve(ApproveExpenseRequest $request, int|string $expense): RedirectResponse
    {
        $expense = $this->findExpense($request->user(), $expense);
        $expense = $this->approvalService->approve(
            $expense,
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Pengeluaran berhasil disetujui.');
    }

    public function reject(RejectExpenseRequest $request, int|string $expense): RedirectResponse
    {
        $expense = $this->findExpense($request->user(), $expense);
        $expense = $this->approvalService->reject(
            $expense,
            $request->validated('rejection_reason'),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Pengeluaran berhasil ditolak.');
    }

    public function destroyProof(
        DeleteExpenseProofRequest $request,
        int|string $expense,
    ): RedirectResponse {
        $expense = $this->findExpense($request->user(), $expense);
        $expense = $this->service->removeProof(
            $expense,
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Bukti pengeluaran berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(User $user, array $filters): Builder
    {
        $query = Expense::query()
            ->accessibleTo($user)
            ->betweenDates($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->when(
                $user->isOwner() && ($filters['branch_id'] ?? null),
                fn (Builder $builder) => $builder->forBranch((int) $filters['branch_id']),
            )
            ->when(
                $filters['expense_category_id'] ?? null,
                fn (Builder $builder, int|string $categoryId) => $builder->where('expense_category_id', $categoryId),
            )
            ->when(
                $filters['created_by'] ?? null,
                fn (Builder $builder, int|string $creatorId) => $builder->where('created_by', $creatorId),
            );
        $search = (string) ($filters['search'] ?? '');

        if ($search !== '') {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(function (Builder $nested) use ($term, $user): void {
                $nested->where('description', 'like', $term)
                    ->orWhereHas('expenseCategory', fn (Builder $category) => $category->where('name', 'like', $term))
                    ->orWhereHas('creator', fn (Builder $creator) => $creator->where('name', 'like', $term));

                if ($user->isOwner()) {
                    $nested->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $term));
                }
            });
        }

        return $query;
    }

    private function findExpense(User $user, int|string $expense): Expense
    {
        return Expense::query()
            ->accessibleTo($user)
            ->whereKey($expense)
            ->firstOrFail();
    }
}
