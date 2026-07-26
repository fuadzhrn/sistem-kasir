<?php

namespace App\Services\Audit;

use InvalidArgumentException;

class AuditActionRegistry
{
    /**
     * @var array<string, string>
     */
    private const MODULES = [
        'authentication' => 'Autentikasi',
        'products' => 'Produk',
        'prices' => 'Harga',
        'stocks' => 'Stok',
        'stock_receipts' => 'Barang Masuk',
        'stock_adjustments' => 'Penyesuaian Stok',
        'stock_transfers' => 'Mutasi Stok',
        'sales' => 'Transaksi',
        'receipts' => 'Nota',
        'sale_voids' => 'Pembatalan Transaksi',
        'expenses' => 'Pengeluaran',
        'expense_categories' => 'Kategori Pengeluaran',
        'users' => 'Pengguna',
        'branches' => 'Cabang',
        'categories' => 'Kategori',
        'units' => 'Satuan',
        'payment_methods' => 'Metode Pembayaran',
    ];

    /**
     * @var array<string, string>
     */
    private const ACTIONS = [
        'login_success' => 'Login berhasil',
        'login_failed' => 'Login gagal',
        'logout' => 'Logout',
        'password_changed' => 'Kata sandi diubah',
        'password_reset_by_owner' => 'Kata sandi direset Owner',
        'user_password_reset' => 'Kata sandi pengguna direset',
        'product_created' => 'Produk dibuat',
        'product_updated' => 'Produk diperbarui',
        'product_status_changed' => 'Status produk diubah',
        'product_image_updated' => 'Foto produk diperbarui',
        'product_image_removed' => 'Foto produk dihapus',
        'product_selling_price_changed' => 'Harga jual diubah',
        'product_purchase_price_changed' => 'Harga beli diubah',
        'product_prices_changed' => 'Harga beli dan jual diubah',
        'initial_stock_created' => 'Stok awal dibuat',
        'initial_stock_corrected' => 'Stok awal dikoreksi',
        'stock_receipt_created' => 'Barang masuk dicatat',
        'stock_adjustment_created' => 'Penyesuaian stok dibuat',
        'stock_transfer_requested' => 'Mutasi stok diminta',
        'stock_transfer_completed' => 'Mutasi stok diselesaikan',
        'stock_transfer_completed_out' => 'Stok mutasi dikeluarkan',
        'stock_transfer_completed_in' => 'Stok mutasi diterima',
        'stock_transfer_rejected' => 'Mutasi stok ditolak',
        'stock_transfer_cancelled' => 'Mutasi stok dibatalkan',
        'sale_created' => 'Transaksi dibuat',
        'receipt_reprint_requested' => 'Cetak ulang nota diminta',
        'sale_voided' => 'Transaksi dibatalkan',
        'sale_void_requested' => 'Permintaan pembatalan transaksi',
        'sale_void_approved' => 'Pembatalan transaksi disetujui',
        'sale_void_rejected' => 'Pembatalan transaksi ditolak',
        'expense_created' => 'Pengeluaran dibuat',
        'expense_updated' => 'Pengeluaran diperbarui',
        'expense_proof_removed' => 'Bukti pengeluaran dihapus',
        'expense_approved' => 'Pengeluaran disetujui',
        'expense_rejected' => 'Pengeluaran ditolak',
        'expense_category_created' => 'Kategori pengeluaran dibuat',
        'expense_category_updated' => 'Kategori pengeluaran diperbarui',
        'expense_category_status_changed' => 'Status kategori pengeluaran diubah',
        'expense_category_deleted' => 'Kategori pengeluaran dihapus',
        'user_created' => 'Pengguna dibuat',
        'user_updated' => 'Pengguna diperbarui',
        'user_role_changed' => 'Role pengguna diubah',
        'user_branch_changed' => 'Cabang pengguna diubah',
        'user_status_changed' => 'Status pengguna diubah',
        'branch_created' => 'Cabang dibuat',
        'branch_updated' => 'Cabang diperbarui',
        'branch_status_changed' => 'Status cabang diubah',
        'category_created' => 'Kategori dibuat',
        'category_updated' => 'Kategori diperbarui',
        'category_status_changed' => 'Status kategori diubah',
        'category_deleted' => 'Kategori dihapus',
        'unit_created' => 'Satuan dibuat',
        'unit_updated' => 'Satuan diperbarui',
        'unit_status_changed' => 'Status satuan diubah',
        'unit_deleted' => 'Satuan dihapus',
        'payment_method_created' => 'Metode pembayaran dibuat',
        'payment_method_updated' => 'Metode pembayaran diperbarui',
        'payment_method_status_changed' => 'Status metode pembayaran diubah',
        'payment_method_deleted' => 'Metode pembayaran dihapus',
    ];

    /**
     * @return array<string, string>
     */
    public function actions(): array
    {
        return self::ACTIONS;
    }

    /**
     * @return array<string, string>
     */
    public function modules(): array
    {
        return self::MODULES;
    }

    public function actionLabel(string $action): string
    {
        return self::ACTIONS[$action] ?? str($action)->replace('_', ' ')->title()->value();
    }

    public function moduleLabel(string $module): string
    {
        return self::MODULES[$module] ?? str($module)->replace('_', ' ')->title()->value();
    }

    public function assertValid(string $action, string $module): void
    {
        if (! array_key_exists($action, self::ACTIONS)) {
            throw new InvalidArgumentException("Aksi audit tidak dikenal: {$action}.");
        }

        if (! array_key_exists($module, self::MODULES)) {
            throw new InvalidArgumentException("Modul audit tidak dikenal: {$module}.");
        }
    }
}
