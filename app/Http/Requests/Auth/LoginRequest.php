<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login_role' => ['required', 'string', Rule::in(['owner', 'admin', 'cashier'])],
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login_role.required' => 'Silakan pilih Owner, Admin Cabang, atau Kasir terlebih dahulu.',
            'login_role.in' => 'Jenis akun yang dipilih tidak tersedia.',
            'login.required' => 'Username atau email wajib diisi.',
            'login.max' => 'Username atau email maksimal 255 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
            'remember.boolean' => 'Pilihan Ingat saya tidak valid.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->credentials();
        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof User || ! $provider->validateCredentials($user, $credentials)) {
            $this->rejectAttempt('login', __('auth.failed'), 'invalid_credentials');
        }

        if (! $user->is_active) {
            $this->rejectAttempt('login', __('auth.inactive'), 'inactive');
        }

        $selectedRole = $this->string('login_role')->value();

        if (! $user->hasRole($selectedRole)) {
            $this->rejectAttempt('login_role', __('auth.role_mismatch'), 'role_mismatch');
        }

        Auth::guard('web')->login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $this->recordFailure('rate_limited');
        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => max(1, (int) ceil($seconds / 60)),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::lower($this->string('login')->trim()->value()).'|'.$this->ip();
    }

    /**
     * @return array<string, string|bool>
     */
    private function credentials(): array
    {
        $login = $this->string('login')->value();
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $this->string('password')->value(),
        ];
    }

    private function rejectAttempt(string $field, string $message, string $reason): never
    {
        RateLimiter::hit($this->throttleKey(), 60);
        $this->recordFailure($reason);

        $this->session()->invalidate();
        $this->session()->regenerateToken();

        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }

    private function recordFailure(string $reason): void
    {
        $identifier = $this->string('login')->value();
        $masked = mb_strlen($identifier) <= 3
            ? str_repeat('*', mb_strlen($identifier))
            : mb_substr($identifier, 0, 2).str_repeat('*', min(8, mb_strlen($identifier) - 2));

        app(AuditLogService::class)->recordSafely(
            action: 'login_failed',
            module: 'authentication',
            description: 'Percobaan login gagal.',
            metadata: [
                'identifier_masked' => $masked,
                'identifier_fingerprint' => hash_hmac('sha256', $identifier, (string) config('app.key')),
                'reason' => $reason,
                'occurred_at' => now()->toIso8601String(),
            ],
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        );
    }

    protected function prepareForValidation(): void
    {
        $login = trim((string) $this->input('login'));
        $loginRole = Str::lower(trim((string) $this->input('login_role')));

        $this->merge([
            'login' => Str::lower($login),
            'login_role' => $loginRole,
        ]);
    }

    protected function failedValidation(Validator $validator): never
    {
        $this->recordFailure('invalid_format');

        parent::failedValidation($validator);
    }
}
