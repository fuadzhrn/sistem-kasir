<?php

namespace App\Services\Report;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class StockReceiptReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = DB::table('stock_receipts as receipt')->join('branches as branch', 'branch.id', '=', 'receipt.branch_id')->join('users as creator', 'creator.id', '=', 'receipt.created_by')
            ->whereBetween('receipt.receipt_date', [$context['range']['date_from'], $context['range']['date_to']])
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('receipt.branch_id', $id))
            ->when(isset($filters['created_by']), fn (Builder $q) => $q->where('receipt.created_by', $filters['created_by']))
            ->when(isset($filters['supplier']), fn (Builder $q) => $q->where('receipt.supplier_name', 'like', $this->like($filters['supplier'])))
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->whereExists(fn (Builder $i) => $i->selectRaw('1')->from('stock_receipt_items as ri')->whereColumn('ri.stock_receipt_id', 'receipt.id')->where('ri.product_id', $filters['product_id'])))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('receipt.receipt_number', 'like', $s)->orWhere('receipt.supplier_name', 'like', $s)->orWhere('creator.name', 'like', $s)->orWhereExists(fn (Builder $i) => $i->selectRaw('1')->from('stock_receipt_items as ri')->join('products as p', 'p.id', '=', 'ri.product_id')->whereColumn('ri.stock_receipt_id', 'receipt.id')->where('p.name', 'like', $s)));
            });
        $total = (clone $query)->selectRaw('COUNT(receipt.id) document_count')->selectRaw('COALESCE(SUM(receipt.total_cost),0) total_cost')->selectRaw('COALESCE(SUM((SELECT COUNT(*) FROM stock_receipt_items sri WHERE sri.stock_receipt_id = receipt.id)),0) item_rows')->first();
        $sort = ['date' => 'receipt.receipt_date', 'number' => 'receipt.receipt_number', 'supplier' => 'receipt.supplier_name', 'cost' => 'receipt.total_cost'][$filters['sort'] ?? 'date'];
        $data = (clone $query)->select(['receipt.id', 'receipt.receipt_number', 'receipt.receipt_date', 'branch.name as branch_name', 'receipt.supplier_name', 'receipt.total_cost', 'creator.name as creator_name', 'receipt.notes'])
            ->selectRaw('(SELECT COUNT(*) FROM stock_receipt_items sri WHERE sri.stock_receipt_id = receipt.id) AS item_count')
            ->selectRaw('(SELECT COALESCE(SUM(quantity),0) FROM stock_receipt_items sri WHERE sri.stock_receipt_id = receipt.id) AS quantity_total')
            ->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('receipt.id');
        $map = fn ($r) => ['number' => $r->receipt_number, 'date' => date('d M Y', strtotime($r->receipt_date)), 'branch' => $r->branch_name, 'supplier' => $r->supplier_name ?: '—', 'items' => number_format((int) $r->item_count, 0, ',', '.'), 'quantity' => $this->quantity($r->quantity_total).' (lintas satuan)', 'cost' => $this->money($r->total_cost), 'creator' => $r->creator_name, 'notes' => $r->notes ?: '—', 'detail_url' => route('stock-receipts.show', $r->id)];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);

        return $this->result('stock-receipts', 'Laporan Barang Masuk', 'Satu baris per dokumen penerimaan barang.', $context, [
            ['key' => 'number', 'label' => 'Nomor', 'link' => 'detail_url'], ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'supplier', 'label' => 'Supplier'], ['key' => 'items', 'label' => 'Jenis Item'], ['key' => 'quantity', 'label' => 'Quantity*'], ['key' => 'cost', 'label' => 'Total Biaya'], ['key' => 'creator', 'label' => 'Pencatat'], ['key' => 'notes', 'label' => 'Catatan'],
        ], $rows, [['label' => 'Dokumen', 'value' => number_format((int) $total->document_count, 0, ',', '.')], ['label' => 'Baris Item', 'value' => number_format((int) $total->item_rows, 0, ',', '.')], ['label' => 'Total Biaya', 'value' => $this->money($total->total_cost)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'products'])]);
    }
}
