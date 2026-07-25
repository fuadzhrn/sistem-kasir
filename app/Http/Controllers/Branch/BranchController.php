<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Requests\Branch\UpdateBranchStatusRequest;
use App\Models\Branch;
use App\Services\Branch\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $branchService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Branch::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;

        $branches = Branch::query()
            ->accessibleTo($request->user())
            ->withCount(['users', 'branchStocks', 'sales'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(function ($searchQuery) use ($term): void {
                    $searchQuery
                        ->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.branches.index', compact('branches', 'search', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('create', Branch::class);

        return view('pages.branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        Gate::authorize('create', Branch::class);
        $branch = $this->branchService->create($request->validated());

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Cabang berhasil dibuat.');
    }

    public function show(Branch $branch): View
    {
        Gate::authorize('view', $branch);
        $branch->loadCount(['users', 'branchStocks', 'stockReceipts', 'sales']);

        return view('pages.branches.show', compact('branch'));
    }

    public function edit(Branch $branch): View
    {
        Gate::authorize('update', $branch);
        $canChangeCode = $this->branchService->canChangeCode($branch);

        return view('pages.branches.edit', compact('branch', 'canChangeCode'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('update', $branch);
        $branch = $this->branchService->update($branch, $request->validated());

        return redirect()
            ->route('branches.show', $branch)
            ->with('status', 'Cabang berhasil diperbarui.');
    }

    public function updateStatus(UpdateBranchStatusRequest $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('updateStatus', $branch);
        $branch = $this->branchService->updateStatus($branch, $request->boolean('is_active'));

        return back()->with(
            'status',
            $branch->is_active ? 'Cabang berhasil diaktifkan.' : 'Cabang berhasil dinonaktifkan.',
        );
    }

    public function showMyBranch(Request $request): View|RedirectResponse
    {
        if ($request->user()->isOwner()) {
            return redirect()->route('branches.index');
        }

        $branch = Branch::query()
            ->accessibleTo($request->user())
            ->withCount(['users', 'branchStocks', 'stockReceipts', 'sales'])
            ->firstOrFail();

        Gate::authorize('view', $branch);

        return view('pages.branches.my-branch', compact('branch'));
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
