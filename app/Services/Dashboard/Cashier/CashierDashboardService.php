<?php

namespace App\Services\Dashboard\Cashier;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use App\Support\Format\Rupiah;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CashierDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $cashier, Branch $branch, array $filters): array
    {
        $baseQuery = Sale::query()->accessibleTo($cashier);

        return [
            'cashier' => [
                'name' => $cashier->name,
                'branch_name' => $branch->name,
                'status' => $cashier->is_active ? 'Aktif' : 'Tidak Aktif',
            ],
            'today' => $this->todaySummary(clone $baseQuery),
            'sales' => $this->history(clone $baseQuery, $filters),
            'filters' => $filters,
            'generated_at' => now()->translatedFormat('d F Y, H.i'),
        ];
    }

    /**
     * @return array{total: int, completed: int, voided: int}
     */
    private function todaySummary(Builder $query): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $summary = $query
            ->whereBetween('transaction_date', [$now->startOfDay(), $now])
            ->whereIn('status', Sale::statuses())
            ->selectRaw('COUNT(id) AS total')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed',
                [Sale::STATUS_COMPLETED],
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS voided',
                [Sale::STATUS_VOIDED],
            )
            ->first();

        return [
            'total' => (int) ($summary?->total ?? 0),
            'completed' => (int) ($summary?->completed ?? 0),
            'voided' => (int) ($summary?->voided ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, int|string>>
     */
    private function history(Builder $query, array $filters): LengthAwarePaginator
    {
        return $query
            ->when(isset($filters['search']), fn (Builder $builder): Builder => $builder
                ->where('invoice_number', 'like', '%'.$filters['search'].'%'))
            ->when(isset($filters['status']), fn (Builder $builder): Builder => $builder
                ->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn (Builder $builder): Builder => $builder
                ->where(
                    'transaction_date',
                    '>=',
                    CarbonImmutable::parse($filters['date_from'], config('app.timezone'))->startOfDay(),
                ))
            ->when(isset($filters['date_to']), fn (Builder $builder): Builder => $builder
                ->where(
                    'transaction_date',
                    '<',
                    CarbonImmutable::parse($filters['date_to'], config('app.timezone'))->addDay()->startOfDay(),
                ))
            ->select([
                'id',
                'branch_id',
                'payment_method_id',
                'invoice_number',
                'transaction_date',
                'total',
                'payment_method_name',
                'status',
            ])
            ->with([
                'branch:id,name',
                'paymentMethod:id,name',
            ])
            ->withCount('items')
            ->latest('transaction_date')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 10))
            ->withQueryString()
            ->through(fn (Sale $sale): array => [
                'invoice_number' => $sale->invoice_number,
                'transaction_date' => $sale->transaction_date->translatedFormat('d M Y, H.i'),
                'items_count' => (int) $sale->items_count,
                'total_formatted' => Rupiah::format($sale->total),
                'payment_method' => $sale->paymentMethod?->name
                    ?? $sale->payment_method_name
                    ?? 'Tidak Diketahui',
                'status' => $sale->statusLabel(),
                'status_variant' => $sale->statusBadgeVariant(),
                'detail_url' => route('sales.show', $sale->getKey()),
                'receipt_url' => route('sales.receipt.reprint', $sale->getKey()),
            ]);
    }
}
