<?php

namespace App\Services\Report;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class StockReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = DB::table('branch_stocks as stock')->join('branches as branch', 'branch.id', '=', 'stock.branch_id')
            ->join('products as product', 'product.id', '=', 'stock.product_id')->join('categories as category', 'category.id', '=', 'product.category_id')->join('units as unit', 'unit.id', '=', 'product.unit_id')
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('stock.branch_id', $id))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('product.category_id', $filters['category_id']))
            ->when(isset($filters['unit_id']), fn (Builder $q) => $q->where('product.unit_id', $filters['unit_id']))
            ->when(($filters['product_status'] ?? 'all') === 'active', fn (Builder $q) => $q->where('product.is_active', true))
            ->when(($filters['product_status'] ?? 'all') === 'inactive', fn (Builder $q) => $q->where('product.is_active', false))
            ->when(($filters['stock_status'] ?? null) === 'out', fn (Builder $q) => $q->where('stock.quantity', '<=', 0))
            ->when(($filters['stock_status'] ?? null) === 'low', fn (Builder $q) => $q->where('stock.quantity', '>', 0)->whereColumn('stock.quantity', '<=', 'product.minimum_stock'))
            ->when(($filters['stock_status'] ?? null) === 'safe', fn (Builder $q) => $q->whereColumn('stock.quantity', '>', 'product.minimum_stock'))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('product.code', 'like', $s)->orWhere('product.barcode', 'like', $s)->orWhere('product.name', 'like', $s)->orWhere('product.brand', 'like', $s)->orWhere('product.size', 'like', $s));
            });
        $totalQuery = (clone $query)->selectRaw('COUNT(stock.id) sku_count')->selectRaw('SUM(CASE WHEN stock.quantity <= 0 THEN 1 ELSE 0 END) out_count')->selectRaw('SUM(CASE WHEN stock.quantity > 0 AND stock.quantity <= product.minimum_stock THEN 1 ELSE 0 END) low_count')->selectRaw('SUM(CASE WHEN stock.quantity > product.minimum_stock THEN 1 ELSE 0 END) safe_count');
        if ($context['access']['can_view_inventory_cost']) {
            $totalQuery->selectRaw('COALESCE(SUM(stock.quantity * stock.average_cost),0) inventory_value');
        }
        $total = $totalQuery->first();
        $sort = ['status' => DB::raw('CASE WHEN stock.quantity <= 0 THEN 0 WHEN stock.quantity <= product.minimum_stock THEN 1 ELSE 2 END'), 'product' => 'product.name', 'quantity' => 'stock.quantity', 'branch' => 'branch.name'][$filters['sort'] ?? 'status'];
        $data = (clone $query)->select(['stock.id', 'branch.name as branch_name', 'product.code', 'product.barcode', 'product.name as product_name', 'product.brand', 'product.size', 'category.name as category_name', 'stock.quantity', 'unit.name as unit_name', 'product.minimum_stock', 'product.is_active']);
        if ($context['access']['can_view_inventory_cost']) {
            $data->addSelect('stock.average_cost')->selectRaw('stock.quantity * stock.average_cost AS inventory_value');
        }
        $data->orderBy($sort, $filters['direction'] ?? 'asc')->orderBy('product.name');
        $map = function ($r) use ($context) {
            $row = ['branch' => $r->branch_name, 'code' => $r->code, 'barcode' => $r->barcode ?: '—', 'product' => $r->product_name, 'category' => $r->category_name, 'quantity' => $this->quantity($r->quantity), 'unit' => $r->unit_name, 'minimum' => $this->quantity($r->minimum_stock), 'status' => (float) $r->quantity <= 0 ? 'Habis' : ((float) $r->quantity <= (float) $r->minimum_stock ? 'Menipis' : 'Aman'), 'product_status' => $r->is_active ? 'Aktif' : 'Nonaktif'];
            if ($context['access']['can_view_inventory_cost']) {
                $row['average_cost'] = $this->money($r->average_cost);
                $row['inventory_value'] = $this->money($r->inventory_value);
            }

            return $row;
        };
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);
        $columns = [['key' => 'branch', 'label' => 'Cabang'], ['key' => 'code', 'label' => 'Kode'], ['key' => 'barcode', 'label' => 'Barcode'], ['key' => 'product', 'label' => 'Produk'], ['key' => 'category', 'label' => 'Kategori'], ['key' => 'quantity', 'label' => 'Quantity'], ['key' => 'unit', 'label' => 'Satuan'], ['key' => 'minimum', 'label' => 'Minimum'], ['key' => 'status', 'label' => 'Status'], ['key' => 'product_status', 'label' => 'Produk']];
        $summary = [['label' => 'Jumlah SKU', 'value' => number_format((int) $total->sku_count, 0, ',', '.')], ['label' => 'Aman', 'value' => number_format((int) $total->safe_count, 0, ',', '.')], ['label' => 'Menipis', 'value' => number_format((int) $total->low_count, 0, ',', '.')], ['label' => 'Habis', 'value' => number_format((int) $total->out_count, 0, ',', '.')]];
        if ($context['access']['can_view_inventory_cost']) {
            $columns[] = ['key' => 'average_cost', 'label' => 'Average Cost'];
            $columns[] = ['key' => 'inventory_value', 'label' => 'Nilai Persediaan'];
            $summary[] = ['label' => 'Nilai Persediaan', 'value' => $this->money($total->inventory_value)];
        }

        return $this->result('stocks', 'Laporan Stok', 'Kondisi stok saat ini; quantity berbeda satuan tidak dijumlahkan.', $context, $columns, $rows, $summary, $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'categories', 'units']), 'period_label' => 'Kondisi saat ini']);
    }
}
