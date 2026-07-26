<?php

namespace App\Services\Report;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class NetProfitReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $granularity = (string) $context['range']['granularity'];
        $searchMatchesPeriod = isset($filters['search'])
            && str_contains(
                mb_strtolower($context['range']['label']),
                mb_strtolower($filters['search']),
            );
        $branchIds = Branch::query()
            ->when($context['access']['branch_id'], fn ($q, int $id) => $q->whereKey($id))
            ->when(isset($filters['search']) && ! $searchMatchesPeriod, fn ($q) => $q->where('name', 'like', $this->like($filters['search'])))
            ->pluck('id');
        $salesExpression = $this->periodExpression('transaction_date', $granularity);
        $expenseExpression = $this->periodExpression('expense_date', $granularity);
        $sales = DB::table('sales')->where('status', Sale::STATUS_COMPLETED)
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('transaction_date', [$context['range']['start'], $context['range']['end']])
            ->selectRaw("$salesExpression AS period_key, branch_id")
            ->selectRaw('COALESCE(SUM(total),0) AS net_sales, COALESCE(SUM(total_cost),0) AS cost')
            ->groupByRaw("$salesExpression, branch_id")->get();
        $expenses = DB::table('expenses')->where('status', Expense::STATUS_APPROVED)
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('expense_date', [$context['range']['date_from'], $context['range']['date_to']])
            ->selectRaw("$expenseExpression AS period_key, branch_id")
            ->selectRaw('COALESCE(SUM(amount),0) AS expenses')
            ->groupByRaw("$expenseExpression, branch_id")->get();
        $branches = Branch::query()->whereIn('id', $branchIds)->pluck('name', 'id');
        $merged = collect();

        foreach ($sales as $row) {
            $key = $row->period_key.'|'.$row->branch_id;
            $merged->put($key, [
                'period_key' => (string) $row->period_key, 'branch_id' => (int) $row->branch_id,
                'net_sales' => (float) $row->net_sales, 'cost' => (float) $row->cost, 'expenses' => 0.0,
            ]);
        }
        foreach ($expenses as $row) {
            $key = $row->period_key.'|'.$row->branch_id;
            $item = $merged->get($key, [
                'period_key' => (string) $row->period_key, 'branch_id' => (int) $row->branch_id,
                'net_sales' => 0.0, 'cost' => 0.0, 'expenses' => 0.0,
            ]);
            $item['expenses'] = (float) $row->expenses;
            $merged->put($key, $item);
        }

        $rawRows = $merged->map(function (array $row) use ($branches): array {
            $row['branch'] = $branches[$row['branch_id']] ?? 'Cabang tidak tersedia';
            $row['gross_profit'] = $row['net_sales'] - $row['cost'];
            $row['net_profit'] = $row['gross_profit'] - $row['expenses'];

            return $row;
        });
        $sort = $filters['sort'] ?? 'period';
        $sortKey = ['period' => 'period_key', 'branch' => 'branch', 'net_sales' => 'net_sales', 'net_profit' => 'net_profit'][$sort];
        $rawRows = ($filters['direction'] ?? 'desc') === 'asc'
            ? $rawRows->sortBy($sortKey)->values()
            : $rawRows->sortByDesc($sortKey)->values();
        $summary = [
            'net_sales' => $rawRows->sum('net_sales'), 'cost' => $rawRows->sum('cost'),
            'gross_profit' => $rawRows->sum('gross_profit'), 'expenses' => $rawRows->sum('expenses'),
            'net_profit' => $rawRows->sum('net_profit'),
        ];
        $mapper = fn (array $row): array => [
            'period' => $this->periodLabel($row['period_key'], $granularity), 'branch' => $row['branch'],
            'net_sales' => $this->money($row['net_sales']), 'cost' => $this->money($row['cost']),
            'gross_profit' => $this->money($row['gross_profit']), 'expenses' => $this->money($row['expenses']),
            'net_profit' => $this->money($row['net_profit']),
        ];
        if ($forPrint) {
            $this->printService->ensureWithinLimit($rawRows->count());
            $rows = $rawRows->map($mapper);
        } else {
            $rows = $this->paginateCollection($rawRows->map($mapper), $filters);
        }

        return $this->result('net-profit', 'Laporan Laba Bersih', 'Agregasi transaksi selesai dan pengeluaran approved.', $context, [
            ['key' => 'period', 'label' => 'Periode'], ['key' => 'branch', 'label' => 'Cabang'],
            ['key' => 'net_sales', 'label' => 'Penjualan Bersih'], ['key' => 'cost', 'label' => 'HPP'],
            ['key' => 'gross_profit', 'label' => 'Laba Kotor'], ['key' => 'expenses', 'label' => 'Pengeluaran'],
            ['key' => 'net_profit', 'label' => 'Laba Bersih'],
        ], $rows, [
            ['label' => 'Penjualan Bersih', 'value' => $this->money($summary['net_sales'])],
            ['label' => 'HPP', 'value' => $this->money($summary['cost'])],
            ['label' => 'Laba Kotor', 'value' => $this->money($summary['gross_profit'])],
            ['label' => 'Pengeluaran Approved', 'value' => $this->money($summary['expenses'])],
            ['label' => 'Laba Bersih', 'value' => $this->money($summary['net_profit'])],
        ], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches']), 'granularity' => $granularity]);
    }

    private function periodExpression(string $column, string $granularity): string
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        return match ($granularity) {
            'yearly' => $sqlite ? "strftime('%Y', $column)" : "DATE_FORMAT($column, '%Y')",
            'monthly' => $sqlite ? "strftime('%Y-%m', $column)" : "DATE_FORMAT($column, '%Y-%m')",
            'weekly' => $sqlite ? "strftime('%Y-W%W', $column)" : "DATE_FORMAT($column, '%x-W%v')",
            default => "DATE($column)",
        };
    }

    private function periodLabel(string $key, string $granularity): string
    {
        if ($granularity === 'daily') {
            return date('d M Y', strtotime($key));
        }

        return $key;
    }
}
