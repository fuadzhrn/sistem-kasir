<?php

namespace App\Services\MasterData;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UnitService
{
    public function create(array $data): Unit
    {
        return DB::transaction(fn (): Unit => Unit::query()->create([
            'name' => $data['name'],
            'symbol' => $data['symbol'] ?? null,
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => true,
        ]));
    }

    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data): Unit {
            $locked = Unit::query()->lockForUpdate()->findOrFail($unit->getKey());
            $locked->update([
                'name' => $data['name'],
                'symbol' => $data['symbol'] ?? null,
                'slug' => $this->uniqueSlug($data['name'], (int) $locked->getKey()),
            ]);

            return $locked->refresh();
        });
    }

    public function updateStatus(Unit $unit, bool $isActive): Unit
    {
        return DB::transaction(function () use ($unit, $isActive): Unit {
            $locked = Unit::query()->lockForUpdate()->findOrFail($unit->getKey());
            $locked->update(['is_active' => $isActive]);

            return $locked->refresh();
        });
    }

    public function deleteIfUnused(Unit $unit): void
    {
        DB::transaction(function () use ($unit): void {
            $locked = Unit::query()->lockForUpdate()->findOrFail($unit->getKey());

            if ($locked->products()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'Satuan tidak dapat dihapus karena sudah digunakan oleh produk.',
                ]);
            }

            $locked->delete();
        });
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'satuan';
        $slug = $base;
        $suffix = 2;

        while (Unit::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
