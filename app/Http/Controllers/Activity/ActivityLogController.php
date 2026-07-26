<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ActivityLogIndexRequest;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\Audit\AuditActionRegistry;
use App\Services\Audit\AuditLogPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly AuditActionRegistry $registry,
        private readonly AuditLogPresenter $presenter,
    ) {}

    public function index(ActivityLogIndexRequest $request): View
    {
        $viewer = $request->user();
        $filters = $request->validated();
        $query = ActivityLog::query()->accessibleTo($viewer);
        $this->applyFilters($query, $filters);
        $summaryQuery = clone $query;
        $perPage = (int) ($filters['per_page'] ?? 25);

        $activityLogs = $query
            ->with(['user.role:id,name,slug', 'branch:id,code,name'])
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ActivityLog $log): array => $this->presenter->presentForUser($log, $viewer));

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'today' => (clone $summaryQuery)->whereDate('created_at', today())->count(),
            'failed_logins' => (clone $summaryQuery)->where('action', 'login_failed')->count(),
            'users' => (clone $summaryQuery)->whereNotNull('user_id')->distinct()->count('user_id'),
        ];

        $users = User::query()
            ->accessibleTo($viewer)
            ->with('role:id,name')
            ->orderBy('name')
            ->get(['id', 'role_id', 'name', 'username']);
        $branches = $viewer->isOwner()
            ? Branch::query()->orderBy('name')->get(['id', 'code', 'name'])
            : collect();

        return view('pages.activities.index', [
            'activityLogs' => $activityLogs,
            'summary' => $summary,
            'filters' => $filters,
            'actionOptions' => $this->registry->actions(),
            'modules' => $this->registry->modules(),
            'users' => $users,
            'branches' => $branches,
            'viewer' => $viewer,
        ]);
    }

    public function show(Request $request, int|string $activityLog): View
    {
        $viewer = $request->user();
        $activityLog = ActivityLog::query()
            ->accessibleTo($viewer)
            ->with(['user.role:id,name,slug', 'branch:id,code,name'])
            ->whereKey($activityLog)
            ->firstOrFail();

        Gate::forUser($viewer)->authorize('view', $activityLog);

        return view('pages.activities.show', [
            'activity' => $this->presenter->presentForUser($activityLog, $viewer),
            'viewer' => $viewer,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;

        $query
            ->when($search, function (Builder $query, string $search): void {
                $term = $this->likeTerm($search);
                $query->where(function (Builder $searchQuery) use ($search, $term): void {
                    $searchQuery
                        ->where('description', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhere('module', 'like', $term)
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', $term)
                            ->orWhere('username', 'like', $term))
                        ->orWhereHas('branch', fn (Builder $branchQuery) => $branchQuery
                            ->where('code', 'like', $term)
                            ->orWhere('name', 'like', $term));

                    if (ctype_digit($search)) {
                        $searchQuery->orWhere('reference_id', (int) $search);
                    }
                });
            })
            ->when(isset($filters['branch']), fn (Builder $query) => $query->where('branch_id', $filters['branch']))
            ->when(isset($filters['user']), fn (Builder $query) => $query->where('user_id', $filters['user']))
            ->when(isset($filters['action']), fn (Builder $query) => $query->where('action', $filters['action']))
            ->when(isset($filters['module']), fn (Builder $query) => $query->where('module', $filters['module']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when(isset($filters['ip']), fn (Builder $query) => $query->where('ip_address', 'like', $this->likeTerm($filters['ip'])));
    }

    private function likeTerm(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }
}
