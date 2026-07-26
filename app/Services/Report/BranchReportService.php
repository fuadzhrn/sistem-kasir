<?php

namespace App\Services\Report;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class BranchReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $sales = DB::table('sales')->whereBetween('transaction_date', [$context['range']['start'], $context['range']['end']])->select('branch_id')->selectRaw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed_count")->selectRaw("SUM(CASE WHEN status='voided' THEN 1 ELSE 0 END) voided_count")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN subtotal ELSE 0 END),0) gross_sales")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN discount_amount ELSE 0 END),0) discounts")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN total ELSE 0 END),0) net_sales")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN total_cost ELSE 0 END),0) cost")->groupBy('branch_id');
        $expenses = DB::table('expenses')->where('status', Expense::STATUS_APPROVED)->whereBetween('expense_date', [$context['range']['date_from'], $context['range']['date_to']])->select('branch_id')->selectRaw('COALESCE(SUM(amount),0) expenses')->groupBy('branch_id');
        $query = DB::table('branches as branch')->leftJoinSub($sales, 'sale_total', 'sale_total.branch_id', '=', 'branch.id')->leftJoinSub($expenses, 'expense_total', 'expense_total.branch_id', '=', 'branch.id')
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('branch.id', $id))
            ->when(($filters['branch_status'] ?? 'all') === 'active', fn (Builder $q) => $q->where('branch.is_active', true))
            ->when(($filters['branch_status'] ?? 'all') === 'inactive', fn (Builder $q) => $q->where('branch.is_active', false))
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('branch.code', 'like', $this->like($filters['search']))->orWhere('branch.name', 'like', $this->like($filters['search']))))
            ->select(['branch.id', 'branch.code', 'branch.name', 'branch.is_active'])->selectRaw('COALESCE(sale_total.completed_count,0) completed_count, COALESCE(sale_total.voided_count,0) voided_count, COALESCE(sale_total.gross_sales,0) gross_sales, COALESCE(sale_total.discounts,0) discounts, COALESCE(sale_total.net_sales,0) net_sales, COALESCE(sale_total.cost,0) cost, COALESCE(expense_total.expenses,0) expenses')->selectRaw('COALESCE(sale_total.net_sales,0)-COALESCE(sale_total.cost,0) gross_profit')->selectRaw('COALESCE(sale_total.net_sales,0)-COALESCE(sale_total.cost,0)-COALESCE(expense_total.expenses,0) net_profit');
        $total = DB::query()->fromSub(clone $query, 'branch_report')->selectRaw('COUNT(*) branch_count')->selectRaw('SUM(completed_count) completed_count')->selectRaw('SUM(voided_count) voided_count')->selectRaw('SUM(gross_sales) gross_sales')->selectRaw('SUM(net_sales) net_sales')->selectRaw('SUM(cost) cost')->selectRaw('SUM(gross_profit) gross_profit')->selectRaw('SUM(expenses) expenses')->selectRaw('SUM(net_profit) net_profit')->first();
        $sort = ['branch' => 'branch.name', 'net_sales' => 'net_sales', 'net_profit' => 'net_profit', 'receipts' => 'completed_count'][$filters['sort'] ?? 'net_sales'];
        $data = $query->orderBy($sort, $filters['direction'] ?? 'desc');
        $map = fn ($r) => ['code' => $r->code, 'branch' => $r->name, 'status' => $r->is_active ? 'Aktif' : 'Nonaktif', 'completed' => number_format((int) $r->completed_count, 0, ',', '.'), 'voided' => number_format((int) $r->voided_count, 0, ',', '.'), 'gross_sales' => $this->money($r->gross_sales), 'discount' => $this->money($r->discounts), 'net_sales' => $this->money($r->net_sales), 'cost' => $this->money($r->cost), 'gross_profit' => $this->money($r->gross_profit), 'expenses' => $this->money($r->expenses), 'net_profit' => $this->money($r->net_profit)];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);

        return $this->result('branches', 'Laporan Per Cabang', 'Agregasi keuangan per cabang.', $context, [
            ['key' => 'code', 'label' => 'Kode'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'status', 'label' => 'Status'], ['key' => 'completed', 'label' => 'Nota Selesai'], ['key' => 'voided', 'label' => 'Nota Batal'], ['key' => 'gross_sales', 'label' => 'Omzet'], ['key' => 'discount', 'label' => 'Diskon'], ['key' => 'net_sales', 'label' => 'Penjualan Bersih'], ['key' => 'cost', 'label' => 'HPP'], ['key' => 'gross_profit', 'label' => 'Laba Kotor'], ['key' => 'expenses', 'label' => 'Pengeluaran'], ['key' => 'net_profit', 'label' => 'Laba Bersih'],
        ], $rows, [['label' => 'Cabang', 'value' => number_format((int) $total->branch_count, 0, ',', '.')], ['label' => 'Nota Selesai', 'value' => number_format((int) $total->completed_count, 0, ',', '.')], ['label' => 'Nota Batal', 'value' => number_format((int) $total->voided_count, 0, ',', '.')], ['label' => 'Omzet', 'value' => $this->money($total->gross_sales)], ['label' => 'Penjualan Bersih', 'value' => $this->money($total->net_sales)], ['label' => 'HPP', 'value' => $this->money($total->cost)], ['label' => 'Laba Kotor', 'value' => $this->money($total->gross_profit)], ['label' => 'Pengeluaran Approved', 'value' => $this->money($total->expenses)], ['label' => 'Laba Bersih', 'value' => $this->money($total->net_profit)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches'])]);
    }
}
