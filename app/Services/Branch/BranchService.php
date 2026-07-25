<?php

namespace App\Services\Branch;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function create(array $data): Branch
    {
        return DB::transaction(fn (): Branch => Branch::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]));
    }

    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data): Branch {
            $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());

            if ($lockedBranch->code !== $data['code'] && ! $this->canChangeCode($lockedBranch)) {
                throw ValidationException::withMessages([
                    'code' => 'Kode cabang tidak dapat diubah karena cabang sudah memiliki transaksi.',
                ]);
            }

            $lockedBranch->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            return $lockedBranch->refresh();
        });
    }

    public function updateStatus(Branch $branch, bool $isActive): Branch
    {
        return DB::transaction(function () use ($branch, $isActive): Branch {
            $lockedBranch = Branch::query()->lockForUpdate()->findOrFail($branch->getKey());

            if (! $isActive && ! $this->canDeactivate($lockedBranch)) {
                throw ValidationException::withMessages([
                    'is_active' => 'Cabang masih memiliki pengguna aktif. Pindahkan atau nonaktifkan pengguna terlebih dahulu.',
                ]);
            }

            $lockedBranch->update(['is_active' => $isActive]);

            return $lockedBranch->refresh();
        });
    }

    public function canChangeCode(Branch $branch): bool
    {
        return ! $branch->sales()->exists();
    }

    public function canDeactivate(Branch $branch): bool
    {
        return ! $branch->users()->where('is_active', true)->exists();
    }
}
