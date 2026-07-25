<?php

namespace App\Services\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Sale;
use App\Services\Sale\SaleCalculator;
use App\Support\Format\Rupiah;

final class OwnerDashboardFinancialService
{
    public function __construct(
        private readonly SaleCalculator $calculator,
    ) {}

    /**
     * @return array<string, array{value: int|string, formatted: string}>
     */
    public function summarize(
        ?Branch $branch,
        OwnerDashboardDateRange $dateRange,
    ): array {
        $sales = Sale::query()
            ->financiallyActive()
            ->when($branch, fn ($query) => $query->where('branch_id', $branch->getKey()))
            ->whereBetween('transaction_date', [$dateRange->start, $dateRange->end])
            ->selectRaw('COALESCE(SUM(subtotal), 0) AS gross_sales')
            ->selectRaw('COALESCE(SUM(total), 0) AS net_sales')
            ->selectRaw('COALESCE(SUM(total_cost), 0) AS total_cost')
            ->selectRaw('COUNT(id) AS receipt_count')
            ->first();

        $grossSales = $this->money($sales?->gross_sales);
        $netSales = $this->money($sales?->net_sales);
        $totalCost = $this->money($sales?->total_cost);
        $grossProfit = $this->calculator->subtractMoney($netSales, $totalCost);
        $approvedExpenses = $this->money(
            Expense::query()
                ->approved()
                ->when($branch, fn ($query) => $query->where('branch_id', $branch->getKey()))
                ->where('expense_date', '>=', $dateRange->start->toDateString())
                ->where('expense_date', '<', $dateRange->end->addDay()->toDateString())
                ->sum('amount'),
        );
        $netProfit = $this->calculator->subtractMoney($grossProfit, $approvedExpenses);
        $receiptCount = (int) ($sales?->receipt_count ?? 0);

        return [
            'gross_sales' => $this->moneyCard($grossSales),
            'net_sales' => $this->moneyCard($netSales),
            'cost_of_goods_sold' => $this->moneyCard($totalCost),
            'gross_profit' => $this->moneyCard($grossProfit),
            'approved_expenses' => $this->moneyCard($approvedExpenses),
            'net_profit' => $this->moneyCard($netProfit),
            'receipt_count' => [
                'value' => $receiptCount,
                'formatted' => number_format($receiptCount, 0, ',', '.'),
            ],
        ];
    }

    private function money(mixed $value): string
    {
        return $this->calculator->centsToMoney(
            $this->calculator->moneyToCents((string) ($value ?? '0')),
        );
    }

    /**
     * @return array{value: string, formatted: string}
     */
    private function moneyCard(string $value): array
    {
        return [
            'value' => $value,
            'formatted' => Rupiah::format($value),
        ];
    }
}
