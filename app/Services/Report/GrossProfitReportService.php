<?php

namespace App\Services\Report;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GrossProfitReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = Sale::query()->accessibleTo($user)->financiallyActive()
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->whereBetween('transaction_date', [$context['range']['start'], $context['range']['end']])
            ->when(isset($filters['cashier_id']), fn (Builder $q) => $q->where('cashier_id', $filters['cashier_id']))
            ->when(isset($filters['payment_method_id']), fn (Builder $q) => $q->where('payment_method_id', $filters['payment_method_id']))
            ->when(isset($filters['search']), fn (Builder $q) => $q->where('invoice_number', 'like', $this->like($filters['search'])));
        $total = (clone $query)->reorder()->selectRaw('COUNT(id) receipts')->selectRaw('COALESCE(SUM(total),0) net_sales')->selectRaw('COALESCE(SUM(total_cost),0) cost')->selectRaw('COALESCE(SUM(total-total_cost),0) profit')->first();
        $sort = ['date' => 'transaction_date', 'invoice' => 'invoice_number', 'net_sales' => 'total', 'profit' => DB::raw('total - total_cost'), 'margin' => 'total'][$filters['sort'] ?? 'date'];
        $data = (clone $query)->select(['id', 'branch_id', 'cashier_id', 'invoice_number', 'transaction_date', 'total', 'total_cost'])
            ->selectRaw('total - total_cost AS calculated_profit')
            ->with(['branch:id,name', 'cashier:id,name'])->orderBy($sort, $filters['direction'] ?? 'desc');
        $map = fn (Sale $s) => ['date' => $s->transaction_date->translatedFormat('d M Y'), 'invoice' => $s->invoice_number, 'branch' => $s->branch?->name ?? '—', 'cashier' => $s->cashier?->name ?? '—', 'net_sales' => $this->money($s->total), 'cost' => $this->money($s->total_cost), 'profit' => $this->money($s->calculated_profit), 'margin' => $this->percent((float) $s->total == 0 ? 0 : ((float) $s->calculated_profit / (float) $s->total * 100))];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);
        $margin = (float) $total->net_sales == 0 ? 0 : (float) $total->profit / (float) $total->net_sales * 100;

        return $this->result('gross-profit', 'Laporan Laba Kotor', 'Laba kotor dan margin per nota selesai.', $context, [
            ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'invoice', 'label' => 'Nota'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'cashier', 'label' => 'Kasir'], ['key' => 'net_sales', 'label' => 'Penjualan Bersih'], ['key' => 'cost', 'label' => 'HPP'], ['key' => 'profit', 'label' => 'Laba Kotor'], ['key' => 'margin', 'label' => 'Margin'],
        ], $rows, [['label' => 'Jumlah Nota', 'value' => number_format((int) $total->receipts, 0, ',', '.')], ['label' => 'Penjualan Bersih', 'value' => $this->money($total->net_sales)], ['label' => 'HPP', 'value' => $this->money($total->cost)], ['label' => 'Laba Kotor', 'value' => $this->money($total->profit)], ['label' => 'Margin Total', 'value' => $this->percent($margin)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'payments'])]);
    }
}
