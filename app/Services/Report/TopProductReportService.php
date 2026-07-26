<?php

namespace App\Services\Report;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TopProductReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $base = DB::table('sale_items as item')->join('sales as sale', 'sale.id', '=', 'item.sale_id')->leftJoin('products as product', 'product.id', '=', 'item.product_id')->leftJoin('categories as category', 'category.id', '=', 'product.category_id')->leftJoin('units as unit', 'unit.id', '=', 'product.unit_id')
            ->where('sale.status', Sale::STATUS_COMPLETED)->whereBetween('sale.transaction_date', [$context['range']['start'], $context['range']['end']])
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('sale.branch_id', $id))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('product.category_id', $filters['category_id']))
            ->when(isset($filters['unit_id']), fn (Builder $q) => $q->where('product.unit_id', $filters['unit_id']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('item.product_code', 'like', $s)->orWhere('item.product_name', 'like', $s)->orWhere('product.brand', 'like', $s)->orWhere('product.size', 'like', $s));
            });
        $grouped = (clone $base)->select(['item.product_code', 'item.product_name', 'item.unit_name'])->selectRaw("COALESCE(category.name,'Tanpa kategori') category_name")->selectRaw('SUM(item.quantity) quantity')->selectRaw('COUNT(DISTINCT sale.id) receipt_count')->selectRaw('SUM(item.subtotal + item.discount_amount) gross_sales')->selectRaw('SUM(item.discount_amount) discounts')->selectRaw('SUM(item.subtotal) net_sales')->selectRaw('SUM(item.quantity * item.cost_price) cost')->selectRaw('SUM(item.subtotal - (item.quantity * item.cost_price)) profit')->groupBy('item.product_code', 'item.product_name', 'item.unit_name', 'category.name');
        $receiptCount = (clone $base)->distinct()->count('sale.id');
        $total = DB::query()->fromSub(clone $grouped, 'ranked')->selectRaw('COUNT(*) product_count')->selectRaw('COALESCE(SUM(receipt_count),0) receipt_occurrences')->selectRaw('COALESCE(SUM(net_sales),0) net_sales')->selectRaw('COALESCE(SUM(cost),0) cost')->selectRaw('COALESCE(SUM(profit),0) profit')->first();
        $sort = ['net_sales' => 'net_sales', 'quantity' => 'quantity', 'receipts' => 'receipt_count', 'product' => 'item.product_name'][$filters['sort'] ?? 'net_sales'];
        $printRowCount = $forPrint
            ? DB::query()->fromSub(clone $grouped, 'print_rows')->count()
            : null;
        $data = $grouped->orderBy($sort, $filters['direction'] ?? 'desc')->orderBy('item.product_name');
        $rankOffset = $forPrint ? 0 : (((int) request()->query('page', 1) - 1) * (int) $filters['per_page']);
        $rank = $rankOffset;
        $map = function ($r) use (&$rank) {
            $rank++;

            return ['rank' => $rank, 'code' => $r->product_code, 'product' => $r->product_name, 'category' => $r->category_name, 'quantity' => $this->quantity($r->quantity), 'unit' => $r->unit_name, 'receipts' => number_format((int) $r->receipt_count, 0, ',', '.'), 'gross_sales' => $this->money($r->gross_sales), 'discount' => $this->money($r->discounts), 'net_sales' => $this->money($r->net_sales), 'cost' => $this->money($r->cost), 'profit' => $this->money($r->profit)];
        };
        if ($forPrint) {
            $this->printService->ensureWithinLimit($printRowCount ?? 0);
            $rows = $data->get()->map($map);
        } else {
            $rows = $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);
        }

        return $this->result('top-products', 'Laporan Produk Terlaris', 'Ranking hanya dari transaksi completed.', $context, [
            ['key' => 'rank', 'label' => '#'], ['key' => 'code', 'label' => 'Kode'], ['key' => 'product', 'label' => 'Produk'], ['key' => 'category', 'label' => 'Kategori'], ['key' => 'quantity', 'label' => 'Quantity'], ['key' => 'unit', 'label' => 'Satuan'], ['key' => 'receipts', 'label' => 'Nota'], ['key' => 'gross_sales', 'label' => 'Omzet'], ['key' => 'discount', 'label' => 'Diskon'], ['key' => 'net_sales', 'label' => 'Penjualan Bersih'], ['key' => 'cost', 'label' => 'HPP'], ['key' => 'profit', 'label' => 'Laba Kotor'],
        ], $rows, [['label' => 'Produk Terjual', 'value' => number_format((int) $total->product_count, 0, ',', '.')], ['label' => 'Jumlah Nota', 'value' => number_format($receiptCount, 0, ',', '.')], ['label' => 'Penjualan Bersih', 'value' => $this->money($total->net_sales)], ['label' => 'HPP', 'value' => $this->money($total->cost)], ['label' => 'Laba Kotor', 'value' => $this->money($total->profit)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'categories', 'units'])]);
    }
}
