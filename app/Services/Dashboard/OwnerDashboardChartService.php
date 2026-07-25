<?php

namespace App\Services\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Sale;
use App\Services\Sale\SaleCalculator;
use App\Support\Format\Rupiah;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class OwnerDashboardChartService
{
    public function __construct(
        private readonly SaleCalculator $calculator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ?Branch $branch,
        OwnerDashboardDateRange $dateRange,
    ): array {
        [$periods, $dateToPeriod] = $this->periodBuckets($dateRange);
        $dailySales = $this->dailySales($branch, $dateRange);
        $dailyExpenses = $this->dailyExpenses($branch, $dateRange);

        foreach ($dailySales as $row) {
            $periodKey = $dateToPeriod[(string) $row->aggregate_date] ?? null;

            if ($periodKey === null) {
                continue;
            }

            $periods[$periodKey]['gross_sales'] += $this->cents($row->gross_sales);
            $periods[$periodKey]['net_sales'] += $this->cents($row->net_sales);
            $periods[$periodKey]['total_cost'] += $this->cents($row->total_cost);
        }

        foreach ($dailyExpenses as $row) {
            $periodKey = $dateToPeriod[(string) $row->aggregate_date] ?? null;

            if ($periodKey !== null) {
                $periods[$periodKey]['expenses'] += $this->cents($row->approved_expenses);
            }
        }

        $labels = [];
        $grossSales = [];
        $netSales = [];
        $grossProfit = [];
        $netProfit = [];

        foreach ($periods as $period) {
            $periodGrossProfit = $period['net_sales'] - $period['total_cost'];
            $labels[] = $period['label'];
            $grossSales[] = $this->wholeRupiah($period['gross_sales']);
            $netSales[] = $this->wholeRupiah($period['net_sales']);
            $grossProfit[] = $this->wholeRupiah($periodGrossProfit);
            $netProfit[] = $this->wholeRupiah($periodGrossProfit - $period['expenses']);
        }

        return [
            'sales_trend' => [
                'labels' => $labels,
                'gross_sales' => $grossSales,
                'net_sales' => $netSales,
                'empty' => array_sum(array_map('abs', $grossSales)) === 0
                    && array_sum(array_map('abs', $netSales)) === 0,
            ],
            'profit_trend' => [
                'labels' => $labels,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'empty' => array_sum(array_map('abs', $grossProfit)) === 0
                    && array_sum(array_map('abs', $netProfit)) === 0,
            ],
            'branch_comparison' => $this->branchComparison($branch, $dateRange),
            'payment_composition' => $this->paymentComposition($branch, $dateRange),
        ];
    }

    private function dailySales(
        ?Branch $branch,
        OwnerDashboardDateRange $dateRange,
    ): Collection {
        return Sale::query()
            ->financiallyActive()
            ->when($branch, fn (Builder $query): Builder => $query->where('branch_id', $branch->getKey()))
            ->whereBetween('transaction_date', [$dateRange->start, $dateRange->end])
            ->selectRaw('DATE(transaction_date) AS aggregate_date')
            ->selectRaw('COALESCE(SUM(subtotal), 0) AS gross_sales')
            ->selectRaw('COALESCE(SUM(total), 0) AS net_sales')
            ->selectRaw('COALESCE(SUM(total_cost), 0) AS total_cost')
            ->groupByRaw('DATE(transaction_date)')
            ->orderBy('aggregate_date')
            ->get();
    }

    private function dailyExpenses(
        ?Branch $branch,
        OwnerDashboardDateRange $dateRange,
    ): Collection {
        return Expense::query()
            ->approved()
            ->when($branch, fn (Builder $query): Builder => $query->where('branch_id', $branch->getKey()))
            ->where('expense_date', '>=', $dateRange->start->toDateString())
            ->where('expense_date', '<', $dateRange->end->addDay()->toDateString())
            ->selectRaw('DATE(expense_date) AS aggregate_date')
            ->selectRaw('COALESCE(SUM(amount), 0) AS approved_expenses')
            ->groupByRaw('DATE(expense_date)')
            ->orderBy('aggregate_date')
            ->get();
    }

    /**
     * @return array{array<string, array<string, int|string>>, array<string, string>}
     */
    private function periodBuckets(OwnerDashboardDateRange $range): array
    {
        $periods = [];
        $dateToPeriod = [];
        $cursor = $range->start->startOfDay();
        $lastDate = $range->end->startOfDay();

        while ($cursor->lessThanOrEqualTo($lastDate)) {
            $key = match ($range->granularity) {
                'weekly' => $cursor->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
                'monthly' => $cursor->startOfMonth()->toDateString(),
                default => $cursor->toDateString(),
            };

            if (! isset($periods[$key])) {
                $periods[$key] = [
                    'label' => $this->periodLabel($cursor, $range),
                    'gross_sales' => 0,
                    'net_sales' => 0,
                    'total_cost' => 0,
                    'expenses' => 0,
                ];
            }

            $dateToPeriod[$cursor->toDateString()] = $key;
            $cursor = $cursor->addDay();
        }

        return [$periods, $dateToPeriod];
    }

    private function periodLabel(
        CarbonImmutable $date,
        OwnerDashboardDateRange $range,
    ): string {
        if ($range->granularity === 'monthly') {
            return $date->translatedFormat('M Y');
        }

        if ($range->granularity === 'weekly') {
            $weekStart = $date->startOfWeek(CarbonImmutable::MONDAY)
                ->max($range->start->startOfDay());
            $weekEnd = $date->endOfWeek(CarbonImmutable::SUNDAY)
                ->min($range->end->startOfDay());

            if ($weekStart->isSameMonth($weekEnd)) {
                return $weekStart->format('j').'–'.$weekEnd->translatedFormat('j M');
            }

            return $weekStart->translatedFormat('j M').'–'.$weekEnd->translatedFormat('j M');
        }

        return $date->translatedFormat('j M');
    }

    /**
     * @return array<string, mixed>
     */
    private function branchComparison(
        ?Branch $selectedBranch,
        OwnerDashboardDateRange $range,
    ): array {
        $branches = Branch::query()
            ->when($selectedBranch, fn (Builder $query): Builder => $query->whereKey($selectedBranch->getKey()))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
        $branchIds = $branches->modelKeys();

        $sales = Sale::query()
            ->financiallyActive()
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('transaction_date', [$range->start, $range->end])
            ->selectRaw('branch_id')
            ->selectRaw('COALESCE(SUM(total), 0) AS net_sales')
            ->selectRaw('COALESCE(SUM(total_cost), 0) AS total_cost')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $expenses = Expense::query()
            ->approved()
            ->whereIn('branch_id', $branchIds)
            ->where('expense_date', '>=', $range->start->toDateString())
            ->where('expense_date', '<', $range->end->addDay()->toDateString())
            ->selectRaw('branch_id')
            ->selectRaw('COALESCE(SUM(amount), 0) AS approved_expenses')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $comparison = $branches->map(function (Branch $branch) use ($sales, $expenses): array {
            $sale = $sales->get($branch->getKey());
            $netSales = $this->cents($sale?->net_sales);
            $grossProfit = $netSales - $this->cents($sale?->total_cost);
            $netProfit = $grossProfit - $this->cents(
                $expenses->get($branch->getKey())?->approved_expenses,
            );

            return [
                'label' => $branch->name,
                'net_sales' => $netSales,
                'net_profit' => $netProfit,
            ];
        })->sortByDesc('net_sales')->values();

        $groupedOthers = false;

        if ($comparison->count() > 12) {
            $top = $comparison->take(11);
            $others = $comparison->slice(11);
            $comparison = $top->push([
                'label' => 'Cabang Lainnya',
                'net_sales' => (int) $others->sum('net_sales'),
                'net_profit' => (int) $others->sum('net_profit'),
            ]);
            $groupedOthers = true;
        }

        return [
            'labels' => $comparison->pluck('label')->all(),
            'net_sales' => $comparison
                ->pluck('net_sales')
                ->map(fn (int $value): int => $this->wholeRupiah($value))
                ->all(),
            'net_profit' => $comparison
                ->pluck('net_profit')
                ->map(fn (int $value): int => $this->wholeRupiah($value))
                ->all(),
            'grouped_others' => $groupedOthers,
            'empty' => $comparison->isEmpty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentComposition(
        ?Branch $branch,
        OwnerDashboardDateRange $range,
    ): array {
        $rows = Sale::query()
            ->financiallyActive()
            ->when($branch, fn (Builder $query): Builder => $query->where('branch_id', $branch->getKey()))
            ->whereBetween('transaction_date', [$range->start, $range->end])
            ->selectRaw("COALESCE(NULLIF(TRIM(payment_method_name), ''), 'Tidak Diketahui') AS method_name")
            ->selectRaw('COALESCE(SUM(total), 0) AS net_sales')
            ->groupByRaw("COALESCE(NULLIF(TRIM(payment_method_name), ''), 'Tidak Diketahui')")
            ->orderByDesc('net_sales')
            ->get();
        $totalCents = $rows->sum(fn ($row): int => $this->cents($row->net_sales));

        return [
            'labels' => $rows->pluck('method_name')->all(),
            'values' => $rows
                ->map(fn ($row): int => $this->wholeRupiah($this->cents($row->net_sales)))
                ->all(),
            'formatted_values' => $rows
                ->map(fn ($row): string => Rupiah::format((string) $row->net_sales))
                ->all(),
            'percentages' => $rows
                ->map(fn ($row): float => $totalCents === 0
                    ? 0.0
                    : round(($this->cents($row->net_sales) / $totalCents) * 100, 2))
                ->all(),
            'empty' => $totalCents === 0,
        ];
    }

    private function cents(mixed $value): int
    {
        return $this->calculator->moneyToCents((string) ($value ?? '0'));
    }

    private function wholeRupiah(int $cents): int
    {
        $rounded = intdiv(abs($cents) + 50, 100);

        return $cents < 0 ? -$rounded : $rounded;
    }
}
