<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        $owner = request()->user();
        $users = User::query()
            ->accessibleTo($owner)
            ->with(['role:id,name,slug', 'branch:id,name'])
            ->orderBy('name')
            ->get(['id', 'role_id', 'branch_id', 'name', 'username', 'is_active']);

        return view('auth.change-password', compact('users'));
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $targetUser = User::query()->findOrFail($request->integer('user_id'));

        Gate::authorize('resetPassword', $targetUser);

        $targetUser->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        if ($request->user()->is($targetUser)) {
            $request->session()->regenerate();
        }

        return redirect()
            ->route('account.password.edit')
            ->with('status', __('auth.password_updated_for', ['name' => $targetUser->name]));
    }
}
