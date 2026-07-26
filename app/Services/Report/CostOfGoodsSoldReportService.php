<?php

namespace App\Services\Report;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CostOfGoodsSoldReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = DB::table('sale_items as item')->join('sales as sale', 'sale.id', '=', 'item.sale_id')
            ->join('branches as branch', 'branch.id', '=', 'sale.branch_id')->join('users as cashier', 'cashier.id', '=', 'sale.cashier_id')
            ->leftJoin('products as product', 'product.id', '=', 'item.product_id')
            ->where('sale.status', Sale::STATUS_COMPLETED)->whereBetween('sale.transaction_date', [$context['range']['start'], $context['range']['end']])
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('sale.branch_id', $id))
            ->when(isset($filters['cashier_id']), fn (Builder $q) => $q->where('sale.cashier_id', $filters['cashier_id']))
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->where('item.product_id', $filters['product_id']))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('product.category_id', $filters['category_id']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('sale.invoice_number', 'like', $s)->orWhere('item.product_code', 'like', $s)->orWhere('item.product_name', 'like', $s)->orWhere('cashier.name', 'like', $s));
            });
        $total = (clone $query)->selectRaw('COUNT(DISTINCT sale.id) receipts')->selectRaw('COALESCE(SUM(item.subtotal),0) net_sales')
            ->selectRaw('COALESCE(SUM(item.quantity * item.cost_price),0) cost')->selectRaw('COALESCE(SUM(item.subtotal - (item.quantity * item.cost_price)),0) profit')->first();
        $sort = ['date' => 'sale.transaction_date', 'invoice' => 'sale.invoice_number', 'product' => 'item.product_name', 'cost' => 'item.cost_price'][$filters['sort'] ?? 'date'];
        $data = (clone $query)->select(['sale.transaction_date', 'sale.invoice_number', 'branch.name as branch_name', 'cashier.name as cashier_name', 'item.product_name', 'item.quantity', 'item.unit_name', 'item.cost_price', 'item.subtotal'])
            ->selectRaw('item.quantity * item.cost_price AS item_cost')->selectRaw('item.subtotal - (item.quantity * item.cost_price) AS item_profit')
            ->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('item.id');
        $map = fn ($r) => ['date' => date('d M Y', strtotime($r->transaction_date)), 'invoice' => $r->invoice_number, 'branch' => $r->branch_name, 'cashier' => $r->cashier_name, 'product' => $r->product_name, 'quantity' => $this->quantity($r->quantity), 'unit' => $r->unit_name, 'unit_cost' => $this->money($r->cost_price), 'cost' => $this->money($r->item_cost), 'net_sales' => $this->money($r->subtotal), 'profit' => $this->money($r->item_profit)];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);

        return $this->result('cost-of-goods-sold', 'Laporan HPP', 'Harga pokok menggunakan cost price snapshot sale item.', $context, [
            ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'invoice', 'label' => 'Nota'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'cashier', 'label' => 'Kasir'], ['key' => 'product', 'label' => 'Produk'], ['key' => 'quantity', 'label' => 'Quantity'], ['key' => 'unit', 'label' => 'Satuan'], ['key' => 'unit_cost', 'label' => 'Modal/Unit'], ['key' => 'cost', 'label' => 'HPP Item'], ['key' => 'net_sales', 'label' => 'Penjualan Bersih'], ['key' => 'profit', 'label' => 'Laba Kotor'],
        ], $rows, [['label' => 'Jumlah Nota', 'value' => number_format((int) $total->receipts, 0, ',', '.')], ['label' => 'Penjualan Bersih', 'value' => $this->money($total->net_sales)], ['label' => 'Total HPP', 'value' => $this->money($total->cost)], ['label' => 'Laba Kotor', 'value' => $this->money($total->profit)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'products', 'categories'])]);
    }
}
