<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ResetUserPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['owner', 'admin', 'cashier'])],
            'branch' => ['nullable', 'integer', 'exists:branches,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $roleFilter = $filters['role'] ?? null;
        $branchFilter = isset($filters['branch']) ? (int) $filters['branch'] : null;
        $status = $filters['status'] ?? null;
        $viewer = $request->user();

        $users = User::query()
            ->accessibleTo($viewer)
            ->with(['role:id,name,slug', 'branch:id,code,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(function ($searchQuery) use ($term): void {
                    $searchQuery
                        ->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($roleFilter !== null, fn ($query) => $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('slug', $roleFilter)))
            ->when($branchFilter !== null, fn ($query) => $query->where('branch_id', $branchFilter))
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::query()
            ->where('is_active', true)
            ->whereIn('slug', ['owner', 'admin', 'cashier'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $branches = Branch::query()
            ->accessibleTo($viewer)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('pages.users.index', compact(
            'users',
            'roles',
            'branches',
            'search',
            'roleFilter',
            'branchFilter',
            'status',
            'viewer',
        ));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('pages.users.create', $this->formOptions());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $user = $this->userService->create($request->validated());

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Pengguna berhasil dibuat.');
    }

    public function show(Request $request, User $user): View
    {
        $user = User::query()
            ->accessibleTo($request->user())
            ->with(['role:id,name,slug', 'branch:id,code,name'])
            ->whereKey($user->getKey())
            ->firstOrFail();

        Gate::authorize('view', $user);

        return view('pages.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);
        $user->load(['role', 'branch']);

        return view('pages.users.edit', [
            ...$this->formOptions(),
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $user = $this->userService->update($user, $request->validated(), $request->user());

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        Gate::authorize($request->boolean('is_active') ? 'activate' : 'deactivate', $user);
        $user = $this->userService->updateStatus(
            $user,
            $request->boolean('is_active'),
            $request->user(),
        );

        return back()->with(
            'status',
            $user->is_active ? 'Pengguna berhasil diaktifkan.' : 'Pengguna berhasil dinonaktifkan.',
        );
    }

    public function editPassword(User $user): View
    {
        Gate::authorize('resetPassword', $user);
        $user->load(['role', 'branch']);

        return view('pages.users.reset-password', compact('user'));
    }

    public function updatePassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);
        $this->userService->resetPassword($user, $request->validated('password'), $request->user());

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Kata sandi pengguna berhasil direset.');
    }

    private function formOptions(): array
    {
        return [
            'roles' => Role::query()
                ->where('is_active', true)
                ->whereIn('slug', ['owner', 'admin', 'cashier'])
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ];
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
