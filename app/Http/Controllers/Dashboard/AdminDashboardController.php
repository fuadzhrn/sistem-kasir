<?php

namespace App\Http\Controllers\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AdminDashboardRequest;
use App\Models\Branch;
use App\Services\Dashboard\Admin\AdminDashboardService;
use App\Services\Dashboard\OwnerDashboardDateRangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly OwnerDashboardDateRangeService $dateRangeService,
        private readonly AdminDashboardService $dashboardService,
    ) {}

    public function index(AdminDashboardRequest $request): View
    {
        [$branch, $dateRange] = $this->context($request);

        return view('pages.dashboard.admin.index', [
            'dashboard' => $this->dashboardService->build($branch, $dateRange),
        ]);
    }

    public function data(AdminDashboardRequest $request): JsonResponse
    {
        [$branch, $dateRange] = $this->context($request);

        try {
            return response()->json(
                [
                    'success' => true,
                    'data' => $this->dashboardService->build($branch, $dateRange),
                ],
                200,
                [],
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Data dashboard cabang belum dapat dimuat. Silakan coba kembali.',
            ], 500);
        }
    }

    /**
     * @return array{0: Branch, 1: OwnerDashboardDateRange}
     */
    private function context(AdminDashboardRequest $request): array
    {
        $user = $request->user();

        abort_if(
            $user->branch_id === null,
            403,
            'Akun Admin belum terhubung dengan cabang.',
        );

        $branch = Branch::query()->find($user->branch_id);

        abort_if(
            $branch === null || ! $branch->is_active,
            403,
            'Cabang operasional akun Admin tidak aktif.',
        );

        $validated = $request->validated();
        $dateRange = $this->dateRangeService->resolve(
            $validated['period'],
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        return [$branch, $dateRange];
    }
}
