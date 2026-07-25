<?php

namespace App\Services\MasterData;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function create(array $data): Category
    {
        return DB::transaction(fn (): Category => Category::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]));
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $locked = Category::query()->lockForUpdate()->findOrFail($category->getKey());
            $locked->update([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], (int) $locked->getKey()),
                'description' => $data['description'] ?? null,
            ]);

            return $locked->refresh();
        });
    }

    public function updateStatus(Category $category, bool $isActive): Category
    {
        return DB::transaction(function () use ($category, $isActive): Category {
            $locked = Category::query()->lockForUpdate()->findOrFail($category->getKey());
            $locked->update(['is_active' => $isActive]);

            return $locked->refresh();
        });
    }

    public function deleteIfUnused(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            $locked = Category::query()->lockForUpdate()->findOrFail($category->getKey());

            if ($locked->products()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'Kategori tidak dapat dihapus karena sudah digunakan oleh produk.',
                ]);
            }

            $locked->delete();
        });
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $suffix = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
