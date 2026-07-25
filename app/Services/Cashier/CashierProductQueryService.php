<?php

namespace App\Services\Cashier;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CashierProductQueryService
{
    public function paginate(
        Branch $branch,
        ?string $search,
        ?int $categoryId,
        int $perPage,
    ): LengthAwarePaginator {
        $normalizedSearch = trim((string) $search);
        $query = Product::query()
            ->leftJoin('branch_stocks', function ($join) use ($branch): void {
                $join
                    ->on('branch_stocks.product_id', '=', 'products.id')
                    ->where('branch_stocks.branch_id', '=', $branch->getKey());
            })
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->where('products.is_active', true)
            ->where('categories.is_active', true)
            ->where('units.is_active', true)
            ->select([
                'products.id',
                'products.code',
                'products.barcode',
                'products.name',
                'products.brand',
                'products.size',
                'products.selling_price',
                'products.minimum_stock',
                'products.image_path',
                'products.updated_at',
                'categories.name as category_name',
                'units.name as unit_name',
                'units.symbol as unit_symbol',
            ])
            ->selectRaw('COALESCE(branch_stocks.quantity, 0) as stock_quantity');

        if ($categoryId !== null) {
            $query->where('products.category_id', $categoryId);
        }

        if ($normalizedSearch !== '') {
            $this->applySearch($query, $normalizedSearch);
            $prefix = $this->escapeLike($normalizedSearch).'%';
            $query->orderByRaw(
                'CASE
                    WHEN products.barcode = ? THEN 0
                    WHEN products.code = ? THEN 1
                    WHEN products.code LIKE ? OR products.barcode LIKE ? THEN 2
                    WHEN products.name LIKE ? THEN 3
                    ELSE 4
                END',
                [
                    $normalizedSearch,
                    $normalizedSearch,
                    $prefix,
                    $prefix,
                    '%'.$this->escapeLike($normalizedSearch).'%',
                ],
            );
        }

        return $query
            ->orderBy('products.name')
            ->orderBy('products.code')
            ->paginate($perPage);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $term = '%'.$this->escapeLike($search).'%';

        $query->where(function (Builder $nested) use ($term): void {
            $nested
                ->where('products.code', 'like', $term)
                ->orWhere('products.barcode', 'like', $term)
                ->orWhere('products.name', 'like', $term)
                ->orWhere('products.brand', 'like', $term)
                ->orWhere('products.size', 'like', $term);
        });
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
