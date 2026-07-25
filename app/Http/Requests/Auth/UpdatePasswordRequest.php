<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()->isOwner();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['user_id', 'password'])) {
                    return;
                }

                $targetUser = User::query()->find($this->integer('user_id'));

                if ($targetUser !== null && Hash::check((string) $this->input('password'), $targetUser->password)) {
                    $validator->errors()->add(
                        'password',
                        'Kata sandi baru harus berbeda dari kata sandi akun yang dipilih.',
                    );
                }
            },
        ];
    }
}
