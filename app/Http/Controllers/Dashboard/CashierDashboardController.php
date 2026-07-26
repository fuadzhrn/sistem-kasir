<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CashierDashboardRequest;
use App\Models\Branch;
use App\Services\Dashboard\Cashier\CashierDashboardService;
use Illuminate\View\View;

class CashierDashboardController extends Controller
{
    public function __construct(
        private readonly CashierDashboardService $dashboardService,
    ) {}

    public function index(CashierDashboardRequest $request): View
    {
        $user = $request->user();

        abort_if(
            $user->branch_id === null,
            403,
            'Akun Kasir belum terhubung dengan cabang.',
        );

        $branch = Branch::query()->find($user->branch_id);

        abort_if(
            $branch === null || ! $branch->is_active,
            403,
            'Cabang operasional akun Kasir tidak aktif.',
        );

        return view('pages.dashboard.cashier.index', [
            'dashboard' => $this->dashboardService->build(
                $user,
                $branch,
                $request->validated(),
            ),
        ]);
    }
}
