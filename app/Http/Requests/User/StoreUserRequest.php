<?php

namespace App\Http\Requests\User;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    private ?Role $selectedRole = null;

    public function authorize(): bool
    {
        return $this->user()?->isOwner() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('is_active', true),
            ],
            'branch_id' => [
                Rule::requiredIf(fn (): bool => $this->roleRequiresBranch()),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $role = $this->role();

                if ($role !== null && ! in_array($role->slug, ['owner', 'admin', 'cashier'], true)) {
                    $validator->errors()->add('role_id', 'Role pengguna tidak diizinkan.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = mb_strtolower(trim((string) $this->input('email')));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'email' => $email === '' ? null : $email,
        ]);

        if ($this->role()?->slug === 'owner') {
            $this->merge(['branch_id' => null]);
        }
    }

    private function role(): ?Role
    {
        return $this->selectedRole ??= Role::query()
            ->whereKey($this->integer('role_id'))
            ->where('is_active', true)
            ->first();
    }

    private function roleRequiresBranch(): bool
    {
        return in_array($this->role()?->slug, ['admin', 'cashier'], true);
    }
}
