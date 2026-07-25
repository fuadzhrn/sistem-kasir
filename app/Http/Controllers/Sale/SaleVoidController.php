<?php

namespace App\Http\Controllers\Sale;

use App\Exceptions\Sale\SaleVoidException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\VoidSaleRequest;
use App\Models\Sale;
use App\Services\Sale\SaleVoidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SaleVoidController extends Controller
{
    public function __construct(
        private readonly SaleVoidService $service,
    ) {}

    public function store(VoidSaleRequest $request, int|string $sale): RedirectResponse|JsonResponse
    {
        $sale = Sale::query()
            ->accessibleTo($request->user())
            ->whereKey($sale)
            ->with(['branch:id,name', 'cashier:id,name', 'paymentMethod:id,name,type', 'saleVoid'])
            ->firstOrFail();

        if (! $sale->isVoided()) {
            Gate::forUser($request->user())->authorize('void', $sale);
        } else {
            Gate::forUser($request->user())->authorize('view', $sale);
        }

        try {
            $saleVoid = $this->service->voidSale(
                $sale,
                $request->user(),
                $request->validated('reason'),
                $request->boolean('refund_confirmed'),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (SaleVoidException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => $exception->errorCode,
                    'message' => $exception->getMessage(),
                ], $exception->httpStatus);
            }

            return back()->withErrors(['void' => $exception->getMessage()]);
        }

        $idempotent = (bool) $saleVoid->getAttribute('void_idempotent');
        $message = $idempotent
            ? 'Transaksi sudah dibatalkan sebelumnya.'
            : 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'idempotent' => $idempotent,
                'data' => [
                    'invoice_number' => $saleVoid->sale->invoice_number,
                    'status' => $saleVoid->sale->status,
                    'voided_at' => $saleVoid->voided_at?->toIso8601String(),
                    'voided_by' => $saleVoid->voider?->name,
                ],
            ]);
        }

        return redirect()
            ->route('sales.show', $saleVoid->sale_id)
            ->with('status', $message);
    }
}
