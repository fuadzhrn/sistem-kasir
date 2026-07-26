<?php

namespace App\Services\Report;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CashierReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $sales = DB::table('sales')->whereBetween('transaction_date', [$context['range']['start'], $context['range']['end']])
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->select('cashier_id', 'branch_id')->selectRaw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed_count")->selectRaw("SUM(CASE WHEN status='voided' THEN 1 ELSE 0 END) voided_count")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN subtotal ELSE 0 END),0) gross_sales")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN discount_amount ELSE 0 END),0) discounts")->selectRaw("COALESCE(SUM(CASE WHEN status='completed' THEN total ELSE 0 END),0) net_sales")->selectRaw("COALESCE(SUM(CASE WHEN status='voided' THEN total ELSE 0 END),0) voided_value")->groupBy('cashier_id', 'branch_id');
        $query = DB::query()->fromSub($sales, 'sale_total')->join('users as cashier', 'cashier.id', '=', 'sale_total.cashier_id')->leftJoin('roles as role', 'role.id', '=', 'cashier.role_id')->leftJoin('branches as branch', 'branch.id', '=', 'sale_total.branch_id')
            ->when(isset($filters['role_id']), fn (Builder $q) => $q->where('cashier.role_id', $filters['role_id']))
            ->when(($filters['user_status'] ?? 'all') === 'active', fn (Builder $q) => $q->where('cashier.is_active', true))
            ->when(($filters['user_status'] ?? 'all') === 'inactive', fn (Builder $q) => $q->where('cashier.is_active', false))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('cashier.name', 'like', $s)->orWhere('cashier.username', 'like', $s)->orWhere('cashier.email', 'like', $s)->orWhere('branch.name', 'like', $s));
            })
            ->select(['cashier.name', 'cashier.username', 'cashier.email', 'cashier.is_active', 'role.name as role_name', 'branch.name as branch_name', 'sale_total.completed_count', 'sale_total.voided_count', 'sale_total.gross_sales', 'sale_total.discounts', 'sale_total.net_sales', 'sale_total.voided_value'])
            ->selectRaw('CASE WHEN sale_total.completed_count > 0 THEN sale_total.net_sales / sale_total.completed_count ELSE 0 END average_receipt');
        $total = DB::query()->fromSub(clone $query, 'cashier_report')->selectRaw('COUNT(*) user_count')->selectRaw('SUM(completed_count) completed_count')->selectRaw('SUM(voided_count) voided_count')->selectRaw('SUM(net_sales) net_sales')->selectRaw('SUM(voided_value) voided_value')->first();
        $sort = ['cashier' => 'cashier.name', 'net_sales' => 'sale_total.net_sales', 'receipts' => 'sale_total.completed_count', 'average' => 'average_receipt'][$filters['sort'] ?? 'net_sales'];
        $data = $query->orderBy($sort, $filters['direction'] ?? 'desc');
        $map = fn ($r) => ['cashier' => $r->name, 'username' => $r->username, 'role' => $r->role_name ?? '—', 'branch' => $r->branch_name ?? '—', 'status' => $r->is_active ? 'Aktif' : 'Nonaktif', 'completed' => number_format((int) $r->completed_count, 0, ',', '.'), 'voided' => number_format((int) $r->voided_count, 0, ',', '.'), 'gross_sales' => $this->money($r->gross_sales), 'discount' => $this->money($r->discounts), 'net_sales' => $this->money($r->net_sales), 'voided_value' => $this->money($r->voided_value), 'average' => $this->money($r->average_receipt)];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);

        return $this->result('cashiers', 'Laporan Per Kasir', 'Ringkasan transaksi per pengguna tanpa komisi atau target.', $context, [
            ['key' => 'cashier', 'label' => 'Pengguna'], ['key' => 'username', 'label' => 'Username'], ['key' => 'role', 'label' => 'Role'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'status', 'label' => 'Status'], ['key' => 'completed', 'label' => 'Nota Selesai'], ['key' => 'voided', 'label' => 'Nota Batal'], ['key' => 'gross_sales', 'label' => 'Omzet'], ['key' => 'discount', 'label' => 'Diskon'], ['key' => 'net_sales', 'label' => 'Penjualan Bersih'], ['key' => 'voided_value', 'label' => 'Nilai Batal'], ['key' => 'average', 'label' => 'Rata-rata Nota'],
        ], $rows, [['label' => 'Pengguna', 'value' => number_format((int) $total->user_count, 0, ',', '.')], ['label' => 'Nota Selesai', 'value' => number_format((int) $total->completed_count, 0, ',', '.')], ['label' => 'Nota Batal', 'value' => number_format((int) $total->voided_count, 0, ',', '.')], ['label' => 'Penjualan Bersih', 'value' => $this->money($total->net_sales)], ['label' => 'Nilai Dibatalkan', 'value' => $this->money($total->voided_value)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'roles'])]);
    }
}
