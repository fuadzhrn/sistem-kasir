<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        $this->auditLog->recordSafely(
            action: 'password_changed',
            module: 'authentication',
            description: 'Owner mengganti kata sandi akun sendiri.',
            actor: $request->user(),
            branch: $request->user()->branch_id,
            reference: $request->user(),
            metadata: ['occurred_at' => now()->toIso8601String()],
        );

        $request->session()->regenerate();

        return redirect()
            ->route('account.password.edit')
            ->with('status', __('auth.password_updated'));
    }
}
