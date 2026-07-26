<?php

namespace App\Services\Report;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ReceiptReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = Sale::query()->accessibleTo($user)
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->whereBetween('transaction_date', [$context['range']['start'], $context['range']['end']])
            ->when(($filters['status'] ?? 'all') !== 'all', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['cashier_id']), fn (Builder $q) => $q->where('cashier_id', $filters['cashier_id']))
            ->when(isset($filters['payment_method_id']), fn (Builder $q) => $q->where('payment_method_id', $filters['payment_method_id']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters, $user): void {
                $search = $this->like($filters['search']);
                $q->where(fn (Builder $s) => $s->where('invoice_number', 'like', $search)
                    ->orWhereHas('cashier', fn (Builder $u) => $u->where('name', 'like', $search))
                    ->orWhere('payment_method_name', 'like', $search)
                    ->when($user->isOwner(), fn (Builder $o) => $o->orWhereHas('branch', fn (Builder $b) => $b->where('name', 'like', $search))));
            });
        $total = (clone $query)->reorder()->selectRaw('COUNT(id) AS receipt_count')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count")
            ->selectRaw("SUM(CASE WHEN status = 'voided' THEN 1 ELSE 0 END) AS voided_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN subtotal ELSE 0 END),0) AS gross_sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN discount_amount ELSE 0 END),0) AS discounts")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END),0) AS net_sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'voided' THEN total ELSE 0 END),0) AS voided_value")->first();
        $sort = ['date' => 'transaction_date', 'invoice' => 'invoice_number', 'total' => 'total', 'status' => 'status'][$filters['sort'] ?? 'date'];
        $dataQuery = (clone $query)->select(['id', 'branch_id', 'cashier_id', 'invoice_number', 'transaction_date', 'subtotal', 'discount_amount', 'total', 'payment_method_name', 'status'])
            ->with(['branch:id,name', 'cashier:id,name'])->withCount('items')
            ->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('id');
        $mapper = fn (Sale $sale): array => [
            'invoice' => $sale->invoice_number, 'date' => $sale->transaction_date->translatedFormat('d M Y, H.i'),
            'branch' => $sale->branch?->name ?? '—', 'cashier' => $sale->cashier?->name ?? '—',
            'items' => number_format($sale->items_count, 0, ',', '.'), 'subtotal' => $this->money($sale->subtotal),
            'discount' => $this->money($sale->discount_amount), 'total' => $this->money($sale->total),
            'payment' => $sale->payment_method_name, 'status' => $sale->statusLabel(),
            'detail' => 'Lihat Detail', 'receipt' => 'Cetak Ulang',
            'detail_url' => route('sales.show', $sale), 'receipt_url' => route('sales.receipt.reprint', $sale),
        ];
        $rows = $forPrint
            ? $this->printableRows($dataQuery, $mapper)
            : $dataQuery->paginate((int) $filters['per_page'])->withQueryString()->through($mapper);

        return $this->result('receipts', 'Laporan Nota', 'Satu baris per nota, termasuk transaksi selesai dan dibatalkan.', $context, [
            ['key' => 'invoice', 'label' => 'Nomor Nota', 'link' => 'detail_url'], ['key' => 'date', 'label' => 'Tanggal'],
            ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'cashier', 'label' => 'Kasir'], ['key' => 'items', 'label' => 'Item'],
            ['key' => 'subtotal', 'label' => 'Subtotal'], ['key' => 'discount', 'label' => 'Diskon'], ['key' => 'total', 'label' => 'Total'],
            ['key' => 'payment', 'label' => 'Pembayaran'], ['key' => 'status', 'label' => 'Status'],
            ['key' => 'detail', 'label' => 'Detail', 'link' => 'detail_url'], ['key' => 'receipt', 'label' => 'Struk', 'link' => 'receipt_url', 'method' => 'post'],
        ], $rows, [
            ['label' => 'Jumlah Nota', 'value' => number_format((int) $total->receipt_count, 0, ',', '.')],
            ['label' => 'Nota Selesai', 'value' => number_format((int) $total->completed_count, 0, ',', '.')],
            ['label' => 'Nota Dibatalkan', 'value' => number_format((int) $total->voided_count, 0, ',', '.')],
            ['label' => 'Omzet Aktif', 'value' => $this->money($total->gross_sales)],
            ['label' => 'Diskon Aktif', 'value' => $this->money($total->discounts)],
            ['label' => 'Penjualan Bersih Aktif', 'value' => $this->money($total->net_sales)],
            ['label' => 'Nilai Dibatalkan', 'value' => $this->money($total->voided_value)],
        ], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'payments'])]);
    }
}
