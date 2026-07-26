<?php

namespace App\Http\Controllers\Receipt;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReceiptReprintController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function store(Request $request, int|string $sale): RedirectResponse
    {
        $user = $request->user();
        $sale = Sale::query()
            ->accessibleTo($user)
            ->whereKey($sale)
            ->firstOrFail();

        Gate::forUser($user)->authorize('print', $sale);

        try {
            $this->auditLog->record(
                action: 'receipt_reprint_requested',
                module: 'receipts',
                description: "Cetak ulang nota {$sale->invoice_number} diminta.",
                actor: $user,
                branch: (int) $sale->branch_id,
                reference: $sale,
                metadata: [
                    'invoice_number' => $sale->invoice_number,
                    'sale_status' => $sale->status,
                ],
            );
        } catch (Throwable $exception) {
            Log::error('Audit cetak ulang nota gagal.', [
                'sale_id' => $sale->getKey(),
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'receipt' => 'Cetak ulang belum dapat diproses karena pencatatan audit gagal.',
            ]);
        }

        return redirect()->route('receipts.print', [
            'sale' => $sale->getKey(),
            'copy' => 1,
        ]);
    }
}
