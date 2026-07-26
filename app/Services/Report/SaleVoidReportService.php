<?php

namespace App\Services\Report;

use App\Models\SaleVoid;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class SaleVoidReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = SaleVoid::query()->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->whereBetween('voided_at', [$context['range']['start'], $context['range']['end']])
            ->when(isset($filters['cashier_id']), fn (Builder $q) => $q->whereHas('sale', fn (Builder $s) => $s->where('cashier_id', $filters['cashier_id'])))
            ->when(isset($filters['voided_by']), fn (Builder $q) => $q->where('voided_by', $filters['voided_by']))
            ->when(isset($filters['payment_method_id']), fn (Builder $q) => $q->whereHas('sale', fn (Builder $s) => $s->where('payment_method_id', $filters['payment_method_id'])))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('reason', 'like', $s)->orWhereHas('sale', fn (Builder $sale) => $sale->where('invoice_number', 'like', $s)->orWhereHas('cashier', fn (Builder $c) => $c->where('name', 'like', $s)))->orWhereHas('voider', fn (Builder $v) => $v->where('name', 'like', $s))->orWhereHas('branch', fn (Builder $b) => $b->where('name', 'like', $s)));
            });
        $total = (clone $query)->reorder()->selectRaw('COUNT(id) void_count')->selectRaw('COALESCE(SUM(original_total),0) total')->selectRaw('COALESCE(SUM(original_total_cost),0) cost')->selectRaw('COALESCE(SUM(original_gross_profit),0) profit')->selectRaw("SUM(CASE WHEN LOWER(payment_method_name)='tunai' THEN 1 ELSE 0 END) cash_count")->selectRaw("SUM(CASE WHEN LOWER(payment_method_name)!='tunai' THEN 1 ELSE 0 END) non_cash_count")->first();
        $sort = ['date' => 'voided_at', 'invoice' => 'sale_id', 'total' => 'original_total', 'profit' => 'original_gross_profit'][$filters['sort'] ?? 'date'];
        $data = (clone $query)->select(['id', 'sale_id', 'branch_id', 'voided_by', 'voided_at', 'reason', 'original_total', 'original_total_cost', 'original_gross_profit', 'payment_method_name', 'refund_confirmed'])->with(['sale' => fn ($q) => $q->select(['id', 'branch_id', 'cashier_id', 'invoice_number', 'transaction_date']), 'sale.cashier:id,name', 'branch:id,name', 'voider:id,name'])->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('id');
        $map = fn (SaleVoid $v) => ['invoice' => $v->sale?->invoice_number ?? '—', 'transaction_date' => $v->sale?->transaction_date?->translatedFormat('d M Y, H.i') ?? '—', 'voided_at' => $v->voided_at->translatedFormat('d M Y, H.i'), 'branch' => $v->branch?->name ?? '—', 'cashier' => $v->sale?->cashier?->name ?? '—', 'voider' => $v->voider?->name ?? '—', 'payment' => $v->payment_method_name, 'reason' => $v->reason, 'total' => $this->money($v->original_total), 'cost' => $this->money($v->original_total_cost), 'profit' => $this->money($v->original_gross_profit), 'refund' => mb_strtolower($v->payment_method_name) === 'tunai' ? 'Tunai' : ($v->refund_confirmed ? 'Dikonfirmasi' : 'Belum dikonfirmasi'), 'detail_url' => $v->sale ? route('sales.show', $v->sale_id) : ''];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);

        return $this->result('sale-voids', 'Laporan Pembatalan Transaksi', 'Histori SaleVoid tanpa menghapus Sale atau SaleItem asli.', $context, [
            ['key' => 'invoice', 'label' => 'Nota', 'link' => 'detail_url'], ['key' => 'transaction_date', 'label' => 'Tanggal Transaksi'], ['key' => 'voided_at', 'label' => 'Tanggal Pembatalan'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'cashier', 'label' => 'Kasir'], ['key' => 'voider', 'label' => 'Dibatalkan Oleh'], ['key' => 'payment', 'label' => 'Pembayaran'], ['key' => 'reason', 'label' => 'Alasan'], ['key' => 'total', 'label' => 'Nilai Dibatalkan'], ['key' => 'cost', 'label' => 'HPP'], ['key' => 'profit', 'label' => 'Laba Kotor'], ['key' => 'refund', 'label' => 'Refund Manual'],
        ], $rows, [['label' => 'Pembatalan', 'value' => number_format((int) $total->void_count, 0, ',', '.')], ['label' => 'Nilai Dibatalkan', 'value' => $this->money($total->total)], ['label' => 'HPP Dibatalkan', 'value' => $this->money($total->cost)], ['label' => 'Laba Dibatalkan', 'value' => $this->money($total->profit)], ['label' => 'Tunai', 'value' => number_format((int) $total->cash_count, 0, ',', '.')], ['label' => 'Non-tunai', 'value' => number_format((int) $total->non_cash_count, 0, ',', '.')]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'payments'])]);
    }
}
