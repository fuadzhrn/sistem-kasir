<?php

namespace App\Http\Controllers\Cashier;

use App\Exceptions\Sale\SaleCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\StoreCashierCheckoutRequest;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Services\Cashier\CashierContextService;
use App\Services\Sale\SaleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class CashierCheckoutController extends Controller
{
    public function __construct(
        private readonly CashierContextService $context,
        private readonly SaleService $sales,
    ) {}

    public function store(StoreCashierCheckoutRequest $request): JsonResponse
    {
        $actor = $request->user()->loadMissing('role');
        Gate::authorize('create', Sale::class);
        $validated = $request->validated();

        try {
            $this->validateRequestedBranch($actor->branch_id, $actor->isOwner(), $validated);
            $branch = $this->context->resolveBranch(
                $actor,
                $actor->isOwner() ? (int) $validated['branch_id'] : null,
            );
            $paymentMethod = PaymentMethod::query()
                ->whereKey($validated['payment_method_id'])
                ->where('is_active', true)
                ->first();

            if ($paymentMethod === null) {
                throw new SaleCheckoutException(
                    'PAYMENT_METHOD_INACTIVE',
                    'Metode pembayaran tidak tersedia atau tidak aktif.',
                    422,
                );
            }

            $sale = $this->sales->createSale(
                actor: $actor,
                branch: $branch,
                items: $validated['items'],
                discountAmount: (string) ($validated['discount_amount'] ?? '0.00'),
                paymentMethod: $paymentMethod,
                amountReceived: isset($validated['amount_received'])
                    ? (string) $validated['amount_received']
                    : null,
                checkoutToken: $validated['checkout_token'],
                paymentAction: $validated['payment_action'],
                notes: $validated['notes'] ?? null,
                expectedSubtotal: isset($validated['expected_subtotal'])
                    ? (string) $validated['expected_subtotal']
                    : null,
                expectedTotal: isset($validated['expected_total'])
                    ? (string) $validated['expected_total']
                    : null,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (SaleCheckoutException $exception) {
            return response()->json([
                'success' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'data' => $exception->data,
            ], $exception->httpStatus);
        } catch (AuthorizationException) {
            return response()->json([
                'success' => false,
                'code' => 'BRANCH_NOT_ALLOWED',
                'message' => 'Anda tidak memiliki akses transaksi pada cabang tersebut.',
            ], 403);
        } catch (Throwable $exception) {
            Log::error('Checkout penjualan gagal.', [
                'user_id' => $actor->getKey(),
                'branch_id' => $actor->isOwner()
                    ? ($validated['branch_id'] ?? null)
                    : $actor->branch_id,
                'product_ids' => collect($validated['items'] ?? [])
                    ->pluck('product_id')
                    ->filter()
                    ->values()
                    ->all(),
                'exception' => $exception::class,
            ]);

            return response()->json([
                'success' => false,
                'code' => 'CHECKOUT_FAILED',
                'message' => 'Transaksi belum dapat diproses. Silakan coba kembali.',
            ], 500);
        }

        $idempotent = (bool) $sale->getAttribute('checkout_idempotent');

        return response()->json([
            'success' => true,
            'message' => $idempotent
                ? 'Transaksi sebelumnya ditemukan.'
                : 'Transaksi berhasil disimpan.',
            'idempotent' => $idempotent,
            'data' => [
                'sale_id' => $sale->getKey(),
                'invoice_number' => $sale->invoice_number,
                'transaction_date' => $sale->transaction_date->toIso8601String(),
                'branch_name' => $sale->branch->name,
                'item_count' => (int) $sale->items_count,
                'subtotal' => $sale->subtotal,
                'discount_amount' => $sale->discount_amount,
                'total' => $sale->total,
                'amount_paid' => $sale->amount_paid,
                'change_amount' => $sale->change_amount,
                'payment_method' => [
                    'code' => $sale->paymentMethod->code,
                    'name' => $sale->payment_method_name,
                    'type' => $sale->paymentMethod->type,
                ],
                'payment_action' => $validated['payment_action'],
                'print_available' => false,
            ],
        ], $idempotent ? 200 : 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateRequestedBranch(
        ?int $actorBranchId,
        bool $isOwner,
        array $validated,
    ): void {
        if (
            ! $isOwner
            && isset($validated['branch_id'])
            && (int) $validated['branch_id'] !== (int) $actorBranchId
        ) {
            throw new SaleCheckoutException(
                'BRANCH_NOT_ALLOWED',
                'Cabang transaksi tidak sesuai dengan cabang akun.',
                403,
            );
        }
    }
}
