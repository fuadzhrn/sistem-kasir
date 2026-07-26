<?php

namespace App\Services\Report;

use App\Models\Branch;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Support\Format\Rupiah;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class AbstractReportService implements ReportService
{
    public function __construct(
        protected readonly ReportAccessContextService $accessService,
        protected readonly ReportDateRangeService $dateRangeService,
        protected readonly ReportPrintService $printService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function foundation(User $user, array $filters): array
    {
        return [
            'access' => $this->accessService->resolve($user, $filters),
            'range' => $this->dateRangeService->resolve($filters),
        ];
    }

    protected function money(mixed $value): string
    {
        return Rupiah::format((string) ($value ?? 0));
    }

    protected function quantity(mixed $value): string
    {
        $formatted = number_format((float) ($value ?? 0), 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    protected function percent(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, ',', '.').'%';
    }

    protected function like(string $value): string
    {
        return '%'.str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value,
        ).'%';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function paginateCollection(Collection $rows, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function printableRows(mixed $query, Closure $mapper): Collection
    {
        $this->printService->ensureWithinLimit((clone $query)->count());

        return $query->get()->map($mapper);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array{label: string, value: string}>  $summary
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function result(
        string $slug,
        string $title,
        string $description,
        array $context,
        array $columns,
        mixed $rows,
        array $summary,
        array $filters,
        bool $forPrint,
        array $extra = [],
    ): array {
        return [
            'slug' => $slug,
            'title' => $title,
            'description' => $description,
            'branch_name' => $context['access']['branch_name'],
            'period_label' => $context['range']['label'],
            'printed_by' => $context['access']['user']->name,
            'printed_at' => now()->translatedFormat('d F Y, H.i'),
            'columns' => $columns,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'active_filters' => $this->activeFilters($filters),
            'filter_options' => [],
            'for_print' => $forPrint,
            'orientation' => count($columns) > 8 ? 'landscape' : 'portrait',
            ...$extra,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label: string, value: string}>
     */
    private function activeFilters(array $filters): array
    {
        $labels = [
            'search' => 'Pencarian',
            'cashier_id' => 'ID Kasir',
            'product_id' => 'ID Produk',
            'category_id' => 'ID Kategori',
            'unit_id' => 'ID Satuan',
            'payment_method_id' => 'ID Pembayaran',
            'created_by' => 'ID Pencatat',
            'changed_by' => 'ID Pengubah',
            'voided_by' => 'ID Pembatal',
            'role_id' => 'ID Role',
            'status' => 'Status',
            'stock_status' => 'Status Stok',
            'product_status' => 'Status Produk',
            'branch_status' => 'Status Cabang',
            'user_status' => 'Status Pengguna',
            'supplier' => 'Supplier',
            'movement_type' => 'Tipe Movement',
            'reference_type' => 'Tipe Referensi',
            'change_type' => 'Jenis Perubahan',
            'granularity' => 'Granularity',
        ];
        $ignored = [
            'period', 'date_from', 'date_to', 'branch_id', 'sort', 'direction',
            'per_page', 'page',
        ];

        return collect($filters)
            ->reject(fn (mixed $value, string $key): bool => in_array($key, $ignored, true)
                || $value === null || $value === '' || $value === 'all')
            ->map(fn (mixed $value, string $key): array => [
                'label' => $labels[$key] ?? str_replace('_', ' ', mb_convert_case($key, MB_CASE_TITLE)),
                'value' => is_scalar($value) ? (string) $value : 'Aktif',
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Branch>
     */
    protected function branchOptions(User $user): Collection
    {
        return $user->isOwner()
            ? Branch::query()->orderBy('name')->get(['id', 'name', 'code', 'is_active'])
            : collect();
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $keys
     * @return array<string, Collection>
     */
    protected function filterOptions(User $user, array $context, array $keys): array
    {
        $options = [];

        foreach ($keys as $key) {
            $options[$key] = match ($key) {
                'branches' => $this->branchOptions($user),
                'users' => User::query()
                    ->when($context['access']['branch_id'], fn ($query, int $id) => $query->where('branch_id', $id))
                    ->orderBy('name')->get(['id', 'name']),
                'products' => Product::query()->orderBy('name')->get(['id', 'code', 'name']),
                'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
                'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
                'payments' => PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
                'expense_categories' => ExpenseCategory::query()->orderBy('name')->get(['id', 'name']),
                'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
                default => collect(),
            };
        }

        return $options;
    }
}
