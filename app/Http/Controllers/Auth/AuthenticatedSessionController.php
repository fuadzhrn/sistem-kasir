<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $request->user()->forceFill([
            'last_login_at' => now(),
        ])->saveQuietly();

        $user = $request->user()->loadMissing(['role:id,slug', 'branch:id,code']);
        $this->auditLog->recordSafely(
            action: 'login_success',
            module: 'authentication',
            description: 'Pengguna berhasil masuk ke aplikasi.',
            actor: $user,
            branch: $user->branch_id,
            reference: $user,
            metadata: [
                'role' => $user->role?->slug,
                'branch_code' => $user->branch?->code,
                'login_method' => 'password',
                'occurred_at' => now()->toIso8601String(),
            ],
        );

        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $this->isSafeIntendedUrl($request, $user, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route($this->dashboardRoute($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->auditLog->recordSafely(
                action: 'logout',
                module: 'authentication',
                description: 'Pengguna keluar dari aplikasi.',
                actor: $user,
                branch: $user->branch_id,
                reference: $user,
                metadata: ['occurred_at' => now()->toIso8601String()],
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', __('auth.logged_out'));
    }

    private function dashboardRoute(User $user): string
    {
        return match (true) {
            $user->isOwner() => 'dashboard.owner',
            $user->isAdmin() => 'dashboard.admin',
            $user->isCashier() => 'dashboard.cashier',
            default => abort(403, 'Role akun tidak memiliki dashboard.'),
        };
    }

    private function isSafeIntendedUrl(Request $request, User $user, string $url): bool
    {
        $isRelative = str_starts_with($url, '/') && ! str_starts_with($url, '//');
        $origin = rtrim($request->getSchemeAndHttpHost(), '/');

        if (! $isRelative && $url !== $origin && ! str_starts_with($url, $origin.'/')) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        try {
            $route = Route::getRoutes()->match(Request::create($path.$query, 'GET'));
        } catch (\Throwable) {
            return false;
        }

        if (
            in_array($route->getName(), [
                'login',
                'login.store',
                'logout',
                'password.request',
                'password.email',
                'password.reset',
                'password.update',
            ], true)
        ) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'role:')) {
                continue;
            }

            $roles = array_filter(explode(',', mb_substr($middleware, 5)));

            if (! $user->hasAnyRole($roles)) {
                return false;
            }
        }

        return true;
    }
}
