<?php

namespace App\Services\Report;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SalesReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = DB::table('sale_items as item')
            ->join('sales as sale', 'sale.id', '=', 'item.sale_id')
            ->join('branches as branch', 'branch.id', '=', 'sale.branch_id')
            ->join('users as cashier', 'cashier.id', '=', 'sale.cashier_id')
            ->leftJoin('products as product', 'product.id', '=', 'item.product_id')
            ->leftJoin('categories as category', 'category.id', '=', 'product.category_id')
            ->whereBetween('sale.transaction_date', [$context['range']['start'], $context['range']['end']])
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('sale.branch_id', $id))
            ->when(($filters['status'] ?? 'completed') !== 'all', fn (Builder $q) => $q->where('sale.status', $filters['status'] ?? Sale::STATUS_COMPLETED))
            ->when(isset($filters['cashier_id']), fn (Builder $q) => $q->where('sale.cashier_id', $filters['cashier_id']))
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->where('item.product_id', $filters['product_id']))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('product.category_id', $filters['category_id']))
            ->when(isset($filters['payment_method_id']), fn (Builder $q) => $q->where('sale.payment_method_id', $filters['payment_method_id']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters): void {
                $search = $this->like($filters['search']);
                $q->where(fn (Builder $s) => $s
                    ->where('sale.invoice_number', 'like', $search)
                    ->orWhere('item.product_code', 'like', $search)
                    ->orWhere('item.product_name', 'like', $search)
                    ->orWhere('product.brand', 'like', $search)
                    ->orWhere('cashier.name', 'like', $search));
            });

        $total = (clone $query)->selectRaw('COUNT(item.id) AS row_count')
            ->selectRaw('COUNT(DISTINCT sale.id) AS receipt_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN sale.status = 'completed' THEN item.subtotal + item.discount_amount ELSE 0 END), 0) AS gross_sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN sale.status = 'completed' THEN item.discount_amount ELSE 0 END), 0) AS discounts")
            ->selectRaw("COALESCE(SUM(CASE WHEN sale.status = 'completed' THEN item.subtotal ELSE 0 END), 0) AS net_sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN sale.status = 'voided' THEN item.subtotal ELSE 0 END), 0) AS voided_value")
            ->first();

        $sort = [
            'date' => 'sale.transaction_date',
            'invoice' => 'sale.invoice_number',
            'product' => 'item.product_name',
            'net_sales' => 'item.subtotal',
        ][$filters['sort'] ?? 'date'];
        $dataQuery = (clone $query)->select([
            'sale.transaction_date', 'sale.invoice_number', 'branch.name as branch_name',
            'cashier.name as cashier_name', 'item.product_code', 'item.product_name',
            'item.product_size', 'item.quantity', 'item.unit_name', 'item.selling_price',
            'item.discount_amount', 'item.subtotal', 'sale.status',
        ])->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('item.id');
        $mapper = fn ($row): array => [
            'date' => date('d M Y, H.i', strtotime($row->transaction_date)),
            'invoice' => $row->invoice_number,
            'branch' => $row->branch_name,
            'cashier' => $row->cashier_name,
            'code' => $row->product_code,
            'product' => $row->product_name,
            'size' => $row->product_size ?: '—',
            'quantity' => $this->quantity($row->quantity),
            'unit' => $row->unit_name,
            'selling_price' => $this->money($row->selling_price),
            'discount' => $this->money($row->discount_amount),
            'net_sales' => $this->money($row->subtotal),
            'status' => $row->status === Sale::STATUS_COMPLETED ? 'Selesai' : 'Dibatalkan',
        ];
        $rows = $forPrint
            ? $this->printableRows($dataQuery, $mapper)
            : $dataQuery->paginate((int) $filters['per_page'])->withQueryString()->through($mapper);

        return $this->result('sales', 'Laporan Penjualan', 'Satu baris per item transaksi menggunakan snapshot penjualan.', $context, [
            ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'invoice', 'label' => 'Nomor Nota'],
            ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'cashier', 'label' => 'Kasir'],
            ['key' => 'code', 'label' => 'Kode'], ['key' => 'product', 'label' => 'Produk'],
            ['key' => 'size', 'label' => 'Ukuran'], ['key' => 'quantity', 'label' => 'Quantity'],
            ['key' => 'unit', 'label' => 'Satuan'], ['key' => 'selling_price', 'label' => 'Harga Jual'],
            ['key' => 'discount', 'label' => 'Diskon Item'], ['key' => 'net_sales', 'label' => 'Penjualan Bersih'],
            ['key' => 'status', 'label' => 'Status'],
        ], $rows, [
            ['label' => 'Baris Item', 'value' => number_format((int) $total->row_count, 0, ',', '.')],
            ['label' => 'Nota Unik', 'value' => number_format((int) $total->receipt_count, 0, ',', '.')],
            ['label' => 'Omzet Aktif', 'value' => $this->money($total->gross_sales)],
            ['label' => 'Diskon Aktif', 'value' => $this->money($total->discounts)],
            ['label' => 'Penjualan Bersih Aktif', 'value' => $this->money($total->net_sales)],
            ['label' => 'Nilai Dibatalkan', 'value' => $this->money($total->voided_value)],
        ], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'products', 'categories', 'payments'])]);
    }
}
