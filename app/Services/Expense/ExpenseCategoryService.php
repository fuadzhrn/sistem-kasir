<?php

namespace App\Services\Expense;

use App\Models\ActivityLog;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryService
{
    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function create(
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ExpenseCategory {
        return DB::transaction(function () use ($data, $actor, $ipAddress, $userAgent): ExpenseCategory {
            $category = ExpenseCategory::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => true,
                'created_by' => $actor->getKey(),
                'updated_by' => null,
            ]);
            $this->log($category, $actor, 'expense_category_created', 'Kategori pengeluaran dibuat.', $ipAddress, $userAgent);

            return $category;
        });
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function update(
        ExpenseCategory $category,
        array $data,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ExpenseCategory {
        return DB::transaction(function () use ($category, $data, $actor, $ipAddress, $userAgent): ExpenseCategory {
            $locked = ExpenseCategory::query()->lockForUpdate()->findOrFail($category->getKey());
            $locked->update([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], (int) $locked->getKey()),
                'description' => $data['description'] ?? null,
                'updated_by' => $actor->getKey(),
            ]);
            $this->log($locked, $actor, 'expense_category_updated', 'Kategori pengeluaran diperbarui.', $ipAddress, $userAgent);

            return $locked->refresh();
        });
    }

    public function updateStatus(
        ExpenseCategory $category,
        bool $active,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ExpenseCategory {
        return DB::transaction(function () use ($category, $active, $actor, $ipAddress, $userAgent): ExpenseCategory {
            $locked = ExpenseCategory::query()->lockForUpdate()->findOrFail($category->getKey());
            $locked->update([
                'is_active' => $active,
                'updated_by' => $actor->getKey(),
            ]);
            $this->log($locked, $actor, 'expense_category_status_changed', 'Status kategori pengeluaran diperbarui.', $ipAddress, $userAgent);

            return $locked->refresh();
        });
    }

    public function deleteIfUnused(
        ExpenseCategory $category,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        DB::transaction(function () use ($category, $actor, $ipAddress, $userAgent): void {
            $locked = ExpenseCategory::query()->lockForUpdate()->findOrFail($category->getKey());

            if ($locked->expenses()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'Kategori tidak dapat dihapus karena sudah digunakan oleh pengeluaran.',
                ]);
            }

            $this->log($locked, $actor, 'expense_category_deleted', 'Kategori pengeluaran dihapus.', $ipAddress, $userAgent);
            $locked->delete();
        });
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori-pengeluaran';
        $slug = $base;
        $suffix = 2;

        while (ExpenseCategory::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function log(
        ExpenseCategory $category,
        User $actor,
        string $action,
        string $description,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        ActivityLog::query()->create([
            'user_id' => $actor->getKey(),
            'branch_id' => null,
            'action' => $action,
            'module' => 'expenses',
            'reference_type' => ExpenseCategory::class,
            'reference_id' => $category->getKey(),
            'description' => $description,
            'ip_address' => $ipAddress ? mb_substr($ipAddress, 0, 45) : null,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 1000) : null,
        ]);
    }
}
