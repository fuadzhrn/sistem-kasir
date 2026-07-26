<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReportIndexController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.reports.index', [
            'reports' => [
                ['slug' => 'sales', 'route' => 'reports.sales.index', 'name' => 'Laporan Penjualan', 'description' => 'Rincian item transaksi dan nilai penjualan.'],
                ['slug' => 'receipts', 'route' => 'reports.receipts.index', 'name' => 'Laporan Nota', 'description' => 'Ringkasan nota selesai dan dibatalkan.'],
                ['slug' => 'cost', 'route' => 'reports.cost-of-goods-sold.index', 'name' => 'Laporan HPP', 'description' => 'Harga pokok berdasarkan snapshot transaksi.'],
                ['slug' => 'gross', 'route' => 'reports.gross-profit.index', 'name' => 'Laporan Laba Kotor', 'description' => 'Penjualan bersih, HPP, laba, dan margin.'],
                ['slug' => 'net', 'route' => 'reports.net-profit.index', 'name' => 'Laporan Laba Bersih', 'description' => 'Laba setelah pengeluaran disetujui.'],
                ['slug' => 'expenses', 'route' => 'reports.expenses.index', 'name' => 'Laporan Pengeluaran', 'description' => 'Pengeluaran berdasarkan status dan tanggal.'],
                ['slug' => 'stocks', 'route' => 'reports.stocks.index', 'name' => 'Laporan Stok', 'description' => 'Kondisi stok terkini per cabang.'],
                ['slug' => 'receiving', 'route' => 'reports.stock-receipts.index', 'name' => 'Laporan Barang Masuk', 'description' => 'Dokumen penerimaan dan biaya pembelian.'],
                ['slug' => 'movements', 'route' => 'reports.stock-movements.index', 'name' => 'Laporan Pergerakan Stok', 'description' => 'Audit perubahan quantity stok.'],
                ['slug' => 'top', 'route' => 'reports.top-products.index', 'name' => 'Laporan Produk Terlaris', 'description' => 'Peringkat produk dari transaksi aktif.'],
                ['slug' => 'branches', 'route' => 'reports.branches.index', 'name' => 'Laporan Per Cabang', 'description' => 'Kinerja keuangan setiap cabang.'],
                ['slug' => 'cashiers', 'route' => 'reports.cashiers.index', 'name' => 'Laporan Per Kasir', 'description' => 'Ringkasan nota dan penjualan per pengguna.'],
                ['slug' => 'prices', 'route' => 'reports.price-histories.index', 'name' => 'Laporan Perubahan Harga', 'description' => 'Audit perubahan harga produk.'],
                ['slug' => 'voids', 'route' => 'reports.sale-voids.index', 'name' => 'Laporan Pembatalan Transaksi', 'description' => 'Histori transaksi yang dibatalkan.'],
            ],
        ]);
    }
}
