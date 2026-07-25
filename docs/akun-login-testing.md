# Akun Login Testing Lokal

Dokumen ini berisi akun demo untuk memeriksa autentikasi, role, menu, dan pembatasan cabang pada lingkungan pengembangan lokal.

> **Peringatan:** akun ini bukan akun produksi. Jangan menjalankan seeder, menggunakan username, atau menggunakan password berikut pada server production.

## Alamat login

```text
http://sistem-kasir.test/login
```

Jika proyek dijalankan dengan `php artisan serve`, gunakan URL yang ditampilkan oleh perintah tersebut, lalu tambahkan `/login`.

## Daftar akun

Semua akun menggunakan password:

```text
TestKasir#2026
```

| Role | Username | Password | Cabang | Tujuan pengujian |
| --- | --- | --- | --- | --- |
| Owner | `test.owner` | `TestKasir#2026` | Semua Cabang | Menguji akses lintas cabang dan menu Owner |
| Admin | `test.admin.a` | `TestKasir#2026` | Cabang Testing A | Menguji akses Admin ke Cabang A |
| Admin | `test.admin.b` | `TestKasir#2026` | Cabang Testing B | Menguji isolasi Admin antar-cabang |
| Kasir | `test.kasir.a` | `TestKasir#2026` | Cabang Testing A | Menguji menu dan akses Kasir Cabang A |
| Kasir | `test.kasir.b` | `TestKasir#2026` | Cabang Testing B | Menguji isolasi Kasir antar-cabang |

Login dapat menggunakan username di atas. Email testing juga tersedia pada database, tetapi username disarankan agar pengujian lebih mudah.

## Membuat atau memperbarui akun

Pastikan `.env` menggunakan:

```text
APP_ENV=local
```

Kemudian jalankan:

```bash
php artisan db:seed --class=LocalAuthorizationTestAccountSeeder
```

Seeder bersifat idempotent: menjalankannya kembali akan memperbarui akun dan mengembalikan password ke nilai yang tercantum di dokumen ini.

Seeder mempunyai pengaman environment dan akan menolak dijalankan jika aplikasi tidak berada pada environment `local`. Seeder tidak didaftarkan pada `DatabaseSeeder`, sehingga tidak ikut berjalan secara tidak sengaja saat menjalankan seeder utama.

## Skenario pemeriksaan

### Owner

1. Login menggunakan `test.owner`.
2. Buka `/authorization-check`.
3. Pastikan Cabang Testing A dan Cabang Testing B terlihat.
4. Buka kedua cabang dan route pemeriksaan laba.
5. Pastikan seluruhnya diizinkan.

### Admin Cabang A

1. Login menggunakan `test.admin.a`.
2. Pastikan sidebar menggunakan menu Admin.
3. Buka `/authorization-check`.
4. Pastikan hanya Cabang Testing A yang terlihat.
5. Buka Cabang Testing A dan pemeriksaan laba Cabang A.
6. Ganti ID URL ke Cabang Testing B; akses harus ditolak.

Ulangi dengan `test.admin.b` untuk memeriksa arah sebaliknya.

### Kasir Cabang A

1. Login menggunakan `test.kasir.a`.
2. Pastikan sidebar hanya menampilkan:
   - Transaksi Baru.
   - Transaksi Saya.
   - Cetak Ulang Nota.
   - Akun Saya.
3. Buka `/authorization-check`.
4. Pastikan akses laba ditolak.
5. Ganti ID URL ke Cabang Testing B; akses harus ditolak.

Ulangi dengan `test.kasir.b` untuk memeriksa arah sebaliknya.

## Catatan

- Modul bisnis pada sidebar masih berstatus “Segera” karena Tahap 5 hanya menyediakan fondasi otorisasi.
- Akun testing tidak berisi transaksi atau data bisnis.
- Hanya akun `test.owner` yang dapat membuka halaman “Kelola Kata Sandi” dan mengganti password akun lain.
- Admin dan Kasir tidak dapat mengganti password sendiri. Jika password testing diubah oleh Owner, jalankan kembali seeder untuk mengembalikannya ke `TestKasir#2026`.
- Ganti atau hapus akun testing sebelum menyalin database lokal ke environment lain.
- Jangan menambahkan credential production ke dokumen ini.
