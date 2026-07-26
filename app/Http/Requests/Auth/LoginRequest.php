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
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $this->recordFailure('rate_limited');
            $this->ensureIsNotRateLimited();
        }

        if (! Auth::attempt($this->credentials())) {
            RateLimiter::hit($this->throttleKey(), 60);
            $this->recordFailure($this->failureReason());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

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
            'is_active' => true,
        ];
    }

    private function failureReason(): string
    {
        $login = $this->string('login')->value();
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::query()->where($field, $login)->first(['id', 'is_active']);

        return $user !== null && ! $user->is_active
            ? 'inactive'
            : 'invalid_credentials';
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

        $this->merge([
            'login' => Str::lower($login),
        ]);
    }

    protected function failedValidation(Validator $validator): never
    {
        $this->recordFailure('invalid_format');

        parent::failedValidation($validator);
    }
}
