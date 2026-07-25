<?php

namespace App\Http\Controllers\Dashboard;

use App\Data\Dashboard\OwnerDashboardDateRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\OwnerDashboardRequest;
use App\Models\Branch;
use App\Services\Dashboard\OwnerDashboardDateRangeService;
use App\Services\Dashboard\OwnerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class OwnerDashboardController extends Controller
{
    public function __construct(
        private readonly OwnerDashboardDateRangeService $dateRangeService,
        private readonly OwnerDashboardService $dashboardService,
    ) {}

    public function index(OwnerDashboardRequest $request): View
    {
        [$branch, $dateRange] = $this->context($request);

        return view('pages.dashboard.owner.index', [
            'branches' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'is_active']),
            'dashboard' => $this->dashboardService->build($branch, $dateRange),
        ]);
    }

    public function data(OwnerDashboardRequest $request): JsonResponse
    {
        try {
            [$branch, $dateRange] = $this->context($request);

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
                'message' => 'Data dashboard belum dapat dimuat. Silakan coba kembali.',
            ], 500);
        }
    }

    /**
     * @return array{0: ?Branch, 1: OwnerDashboardDateRange}
     */
    private function context(OwnerDashboardRequest $request): array
    {
        $validated = $request->validated();
        $branch = isset($validated['branch_id'])
            ? Branch::query()->findOrFail($validated['branch_id'])
            : null;
        $dateRange = $this->dateRangeService->resolve(
            $validated['period'],
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        return [$branch, $dateRange];
    }
}
