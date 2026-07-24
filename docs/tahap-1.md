# Dokumentasi Tahap 1 — Audit dan Inisialisasi

Dokumen ini mencatat fondasi teknis aplikasi kasir tanpa menambahkan fitur bisnis.

## Stack terverifikasi

- PHP 8.3.16.
- Laravel Framework 13.21.1.
- Composer 2.8.11.
- MySQL Community Server 8.4.3.
- Blade, CSS biasa, dan Vanilla JavaScript.
- Database lokal: `sistem_toko`.

## Konfigurasi lokal

Gunakan nilai lokal berikut di `.env` dan sesuaikan credential MySQL dengan instalasi masing-masing:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_toko

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

Jangan menyalin `.env` ke Git atau membagikan `APP_KEY` dan password database.

## Menjalankan proyek

1. Pastikan PHP 8.3+, Composer, dan MySQL aktif.
2. Buat database MySQL bernama `sistem_toko` dengan charset `utf8mb4`.
3. Salin `.env.example` menjadi `.env`.
4. Isi username dan password MySQL lokal pada `.env`.
5. Jalankan `composer install`.
6. Jalankan `php artisan key:generate` jika `APP_KEY` masih kosong.
7. Jalankan `php artisan migrate`.
8. Jalankan `php artisan serve`.
9. Buka `http://127.0.0.1:8000`.
10. Pada environment lokal, buka `http://127.0.0.1:8000/system-check`.

Laragon juga dapat melayani aplikasi melalui `http://sistem-kasir.test` jika automatic
virtual host aktif.

## Pemeriksaan keamanan

Route `/system-check` mengembalikan 404 ketika environment bukan `local`. Halaman hanya
menampilkan status boolean atau versi runtime yang aman. Credential database, nilai
`APP_KEY`, isi `.env`, dan path server tidak ditampilkan.

## Asset

Asset fondasi berada di `public/assets`. File unggahan pengguna tidak boleh ditempatkan
di sana; gunakan `storage/app/public` dan symbolic link Laravel pada tahap berikutnya.
Folder `public/assets/vendor/chartjs` hanya disiapkan dan Chart.js belum dipasang.

## Catatan shared hosting Hostinger

- Pilih versi PHP yang memenuhi `composer.json`, saat ini PHP `^8.3`.
- Aktifkan ekstensi umum Laravel, terutama Ctype, cURL, DOM/XML, Fileinfo, Filter,
  Hash, Mbstring, OpenSSL, PDO, PDO MySQL, Session, Tokenizer, dan XMLWriter.
- Arahkan document root domain ke folder `public`.
- Isi `.env` produksi dengan `APP_ENV=production` dan `APP_DEBUG=false`.
- Gunakan MySQL hosting dan credential dari panel Hostinger.
- Pastikan `storage` dan `bootstrap/cache` dapat ditulis oleh proses web.
- Jalankan cache konfigurasi hanya setelah `.env` produksi final.
- Jangan mengandalkan Redis, WebSocket, queue worker permanen, atau proses daemon pada
  shared hosting. Konfigurasi sinkron dan berbasis file dipilih untuk fondasi ini.

## Batas Tahap 1

Folder modul bisnis hanya berupa placeholder `.gitkeep`. Tidak ada halaman, controller,
route, migration, model, atau logika bisnis yang ditambahkan.
