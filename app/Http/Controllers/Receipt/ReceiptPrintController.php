<?php

namespace App\Http\Controllers\Receipt;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Receipt\ReceiptViewDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ReceiptPrintController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const SAFE_SALE_COLUMNS = [
        'id',
        'branch_id',
        'cashier_id',
        'payment_method_id',
        'invoice_number',
        'transaction_date',
        'subtotal',
        'discount_amount',
        'total',
        'amount_paid',
        'change_amount',
        'payment_method_name',
        'status',
        'notes',
    ];

    /**
     * @var array<int, string>
     */
    private const SAFE_ITEM_COLUMNS = [
        'id',
        'sale_id',
        'product_code',
        'product_name',
        'unit_name',
        'product_size',
        'quantity',
        'selling_price',
        'discount_amount',
        'subtotal',
    ];

    public function __construct(
        private readonly ReceiptViewDataService $receiptData,
    ) {}

    public function show(Request $request, int|string $sale): Response
    {
        $user = $request->user();
        $sale = Sale::query()
            ->accessibleTo($user)
            ->whereKey($sale)
            ->select(self::SAFE_SALE_COLUMNS)
            ->with([
                'branch:id,name,address,phone',
                'cashier:id,name',
                'paymentMethod:id,name',
                'items' => fn ($items) => $items
                    ->select(self::SAFE_ITEM_COLUMNS)
                    ->orderBy('id'),
            ])
            ->firstOrFail();

        Gate::forUser($user)->authorize('print', $sale);

        return response()
            ->view('pages.receipts.print', [
                'receipt' => $this->receiptData->build($sale),
                'isCopy' => $request->query('copy') === '1',
            ])
            ->withHeaders([
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
    }
}
