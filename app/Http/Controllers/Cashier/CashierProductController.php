<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\CashierProductRequest;
use App\Http\Resources\Cashier\CashierProductResource;
use App\Models\Sale;
use App\Services\Cashier\CashierContextService;
use App\Services\Cashier\CashierProductQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CashierProductController extends Controller
{
    public function __construct(
        private readonly CashierContextService $context,
        private readonly CashierProductQueryService $products,
    ) {}

    public function index(CashierProductRequest $request): JsonResponse
    {
        Gate::authorize('create', Sale::class);
        $validated = $request->validated();
        $user = $request->user();
        $branch = $this->context->resolveBranch(
            $user,
            $user->isOwner() ? (int) $validated['branch_id'] : null,
        );
        $paginator = $this->products->paginate(
            $branch,
            $validated['search'] ?? null,
            isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            (int) ($validated['per_page'] ?? 24),
        );

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn ($product): array => CashierProductResource::make($product)->resolve($request))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
