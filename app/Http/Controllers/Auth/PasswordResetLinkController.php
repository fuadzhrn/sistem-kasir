<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $owner = User::query()
            ->where('email', $request->validated('email'))
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', 'owner'))
            ->first();

        if ($owner !== null) {
            Password::sendResetLink([
                'email' => $owner->email,
                'is_active' => true,
                'role_id' => $owner->role_id,
            ]);
        }

        return back()->with('status', __('passwords.sent'));
    }
}
