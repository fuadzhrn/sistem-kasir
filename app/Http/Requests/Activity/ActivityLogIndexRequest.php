<?php

namespace App\Http\Requests\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Audit\AuditActionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ActivityLog::class) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $registry = app(AuditActionRegistry::class);
        $isOwner = $this->user()?->isOwner() === true;

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'branch' => [$isOwner ? 'nullable' : 'prohibited', 'integer', 'exists:branches,id'],
            'user' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::in(array_keys($registry->actions()))],
            'module' => ['nullable', Rule::in(array_keys($registry->modules()))],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:date_from'],
            'ip' => [$isOwner ? 'nullable' : 'prohibited', 'string', 'max:45'],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $userId = $this->integer('user');
                $viewer = $this->user();

                if ($userId <= 0 || $viewer?->isOwner()) {
                    return;
                }

                $isAccessible = User::query()
                    ->accessibleTo($viewer)
                    ->whereKey($userId)
                    ->exists();

                if (! $isAccessible) {
                    $validator->errors()->add('user', 'Pengguna yang dipilih tidak tersedia pada cabang Anda.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch.prohibited' => 'Filter cabang hanya tersedia untuk Owner.',
            'ip.prohibited' => 'Filter alamat IP hanya tersedia untuk Owner.',
            'action.in' => 'Aksi audit tidak valid.',
            'module.in' => 'Modul audit tidak valid.',
            'date_from.before_or_equal' => 'Tanggal awal tidak boleh melewati hari ini.',
            'date_to.before_or_equal' => 'Tanggal akhir tidak boleh melewati hari ini.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'per_page.in' => 'Jumlah data per halaman harus 25, 50, atau 100.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->exists('search')) {
            $normalized['search'] = trim((string) $this->input('search')) ?: null;
        }

        if ($this->exists('ip')) {
            $normalized['ip'] = trim((string) $this->input('ip')) ?: null;
        }

        $this->merge($normalized);
    }
}
