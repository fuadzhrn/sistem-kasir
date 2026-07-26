<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (is_string($intended) && $this->isSafeIntendedUrl($request, $intended)) {
            return redirect()->to($intended);
        }

        return redirect()->route('dashboard');
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

    private function isSafeIntendedUrl(Request $request, string $url): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $origin = rtrim($request->getSchemeAndHttpHost(), '/');

        return $url === $origin || str_starts_with($url, $origin.'/');
    }
}
