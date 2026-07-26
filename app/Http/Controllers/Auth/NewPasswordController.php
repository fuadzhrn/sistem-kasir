<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->value(),
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $owner = User::query()
            ->where('email', $request->validated('email'))
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'owner'))
            ->first();

        if ($owner === null) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.reset_failed')]);
        }

        $status = Password::reset(
            [
                ...$request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
                'is_active' => true,
                'role_id' => $owner->role_id,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.reset_failed')]);
        }

        $owner->refresh();
        $this->auditLog->recordSafely(
            action: 'password_changed',
            module: 'authentication',
            description: 'Owner mengubah kata sandi melalui pemulihan akun.',
            actor: $owner,
            branch: null,
            reference: $owner,
            metadata: [
                'user_id' => $owner->getKey(),
                'self_service' => false,
                'reset_method' => 'password_broker',
                'occurred_at' => now()->toIso8601String(),
            ],
        );

        return redirect()
            ->route('login')
            ->with('status', __('passwords.reset'));
    }
}
