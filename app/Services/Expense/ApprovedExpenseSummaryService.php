<?php

namespace App\Services\Expense;

use App\Models\Branch;
use App\Models\Expense;
use Carbon\CarbonInterface;

class ApprovedExpenseSummaryService
{
    public function totalForPeriod(
        ?Branch $branch,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
    ): string {
        $total = Expense::query()
            ->approved()
            ->when($branch, fn ($query) => $query->forBranch((int) $branch->getKey()))
            ->betweenDates($dateFrom->toDateString(), $dateTo->toDateString())
            ->sum('amount');

        return number_format((float) $total, 2, '.', '');
    }
}
