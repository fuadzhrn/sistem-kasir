# Tahap 5 — Role dan Pembatasan Cabang

## 1. Tujuan

Tahap ini membangun fondasi otorisasi berbasis role dan cabang pada backend Laravel. Perlindungan tidak bergantung pada visibilitas menu. Setiap akses penting harus melewati middleware, Gate atau Policy, dan query scope yang sesuai.

Tahap ini tidak membuat CRUD cabang, pengguna, produk, stok, transaksi, pengeluaran, dashboard bisnis, atau laporan laba sebenarnya.

## 2. Role

Pemeriksaan menggunakan `roles.slug` dari database:

- `owner` — Owner.
- `admin` — Admin/Kepala Cabang.
- `cashier` — Kasir/Pegawai.

Role dari request, hidden input, query string, dan session bukan sumber kebenaran. Model `User` menyediakan `hasRole()`, `hasAnyRole()`, `isOwner()`, `isAdmin()`, dan `isCashier()`.

## 3. Matriks hak akses

| Kemampuan | Owner | Admin | Kasir |
| --- | --- | --- | --- |
| Mengakses cabang | Semua cabang | Cabang akun | Cabang akun |
| Melihat data gabungan | Ya | Tidak | Tidak |
| Menggunakan kasir | Ya | Ya, cabang akun | Ya, cabang akun |
| Melihat transaksi | Semua | Semua di cabang akun | Transaksi sendiri |
| Melihat produk | Semua | Semua | Produk aktif |
| Menambah/mengubah produk | Ya | Ya | Tidak |
| Mengelola stok | Semua cabang | Cabang akun | Hanya membaca untuk transaksi |
| Melihat pengeluaran | Semua | Cabang akun | Tidak |
| Melihat laba | Semua cabang | Cabang akun | Tidak |
| Melihat laporan global | Ya | Tidak | Tidak |
| Mengelola cabang | Ya | Tidak | Tidak |
| Mengelola pengguna | Ya | Belum pada tahap ini | Tidak |
| Mengganti password pengguna | Ya, seluruh akun | Tidak | Tidak |
| Melihat aktivitas | Semua | Cabang akun | Tidak |
| Mengelola pengaturan | Ya | Tidak | Tidak |

Admin tidak dapat melihat akun Owner melalui scope pengguna. Kasir tidak dapat melihat transaksi kasir lain meskipun masih dalam cabang yang sama.

## 4. Empat lapisan keamanan

1. **Middleware role** memeriksa autentikasi, status akun, dan slug role yang diizinkan.
2. **Middleware cabang** memeriksa model `Branch` hasil route model binding dan menolak cabang lain.
3. **Gate dan Policy** memeriksa kemampuan terhadap aksi atau model.
4. **Query scope** membatasi data pada SQL sebelum data diambil.

Urutan yang direkomendasikan:

```text
request → auth → active.user → role/branch.access → controller
        → query scope → Gate/Policy → response
```

## 5. Middleware

### EnsureUserHasRole

Alias: `role`.

Middleware menerima satu atau beberapa slug:

```php
Route::get('/owner', $handler)->middleware('role:owner');
Route::get('/management', $handler)->middleware('role:owner,admin');
```

Guest diarahkan ke login. Pengguna nonaktif atau role yang tidak sesuai memperoleh `403`.

### EnsureBranchAccess

Alias: `branch.access`.

Middleware mengambil parameter `Branch` dari route model binding:

```php
Route::get('/branches/{branch}', $handler)
    ->middleware('branch.access');
```

Owner dapat mengakses semua cabang yang ada. Admin dan Kasir hanya dapat mengakses cabang pada akun. Cabang lain atau parameter yang tidak terikat sebagai `Branch` menghasilkan `404`.

Alias didaftarkan pada `bootstrap/app.php`, sesuai arsitektur Laravel proyek.

## 6. BranchAccessService

`App\Services\Authorization\BranchAccessService` memusatkan keputusan akses cabang tanpa menjalankan query bisnis.

- `canAccessBranch(User $user, Branch|int $branch): bool` — memeriksa apakah cabang dapat diakses.
- `accessibleBranchIds(User $user): array` — memberikan ID cabang yang dapat diakses.
- `resolveBranchId(User $user, ?int $requestedBranchId = null): int` — menentukan ID cabang aman untuk operasi mendatang.
- `isSameBranch(User $user, Branch|int $branch): bool` — membandingkan cabang akun.

Owner wajib memilih cabang aktif yang valid ketika suatu operasi memerlukan satu cabang. Untuk Admin dan Kasir, `requestedBranchId` diabaikan dan cabang akun menjadi sumber kebenaran. Akun nonaktif, cabang akun kosong, atau cabang akun tidak aktif ditolak.

## 7. Query scope

Semua scope bernama `accessibleTo(User $user)` dan harus dipanggil secara eksplisit:

- `Branch` — Owner semua; selain Owner hanya cabang akun; tanpa cabang menghasilkan data kosong.
- `User` — Owner semua; Admin pengguna cabangnya selain Owner; Kasir dirinya sendiri.
- `Sale` — Owner semua; Admin seluruh sale cabangnya; Kasir sale miliknya di cabang akun.
- `Expense` — Owner semua; Admin cabangnya; Kasir tidak memperoleh data.
- `BranchStock` — Owner semua; selain Owner hanya cabang akun.
- `StockReceipt` — Owner semua; selain Owner hanya cabang akun.
- `StockMovement` — Owner semua; selain Owner hanya cabang akun.
- `ActivityLog` — Owner semua; Admin cabangnya; Kasir tidak memperoleh data.

Scope generik model bercabang menggunakan trait `HasBranchAccessScope`. `Sale`, `Expense`, dan `ActivityLog` menggunakan implementasi khusus karena aturannya berbeda.

Contoh aman:

```php
$sales = Sale::query()
    ->accessibleTo(auth()->user())
    ->latest()
    ->get();
```

Jangan mengambil semua data dengan `get()` kemudian memfilternya menggunakan Collection PHP. Pembatasan wajib menjadi bagian dari query SQL.

Tidak ada global scope yang digunakan agar seeder, command, migration, test, dan proses Owner tidak berubah secara tersembunyi.

## 8. Policies

- `BranchPolicy` — Owner mengelola; Admin/Kasir hanya melihat cabangnya. Penghapusan Owner ditolak jika cabang sudah mempunyai relasi penting.
- `ProductPolicy` — Owner dan Admin dapat melihat, membuat, serta mengubah. Kasir hanya melihat produk aktif. Hanya Owner dapat menghapus produk yang belum digunakan.
- `BranchStockPolicy` — Owner semua cabang; Admin mengelola cabangnya; Kasir hanya membaca stok cabangnya.
- `SalePolicy` — Owner semua; Admin sale cabangnya; Kasir sale miliknya. Kasir dapat meminta void tetapi tidak menyetujuinya.
- `ExpensePolicy` — Owner semua; Admin melihat, membuat, dan mengubah di cabangnya; Kasir ditolak. Persetujuan dan penghapusan dibatasi ke Owner pada fondasi tahap ini.
- `UserPolicy` — Owner mengelola semua; Admin hanya melihat pengguna non-Owner di cabangnya; Kasir hanya dirinya.
- `ActivityLogPolicy` — Owner melihat semua; Admin hanya cabangnya; Kasir ditolak.

Contoh pemakaian di controller:

```php
$this->authorize('view', $sale);
```

Seluruh Policy didaftarkan eksplisit pada `AppServiceProvider`.

## 9. Gates

- `view-profit` — Owner semua cabang, Admin hanya cabangnya, Kasir ditolak.
- `view-global-report` — hanya Owner.
- `manage-branches` — hanya Owner.
- `manage-users` — hanya Owner pada Tahap 5.
- `manage-settings` — hanya Owner.
- `view-activity-logs` — Owner dan Admin yang memiliki cabang; datanya tetap dibatasi scope.

Contoh:

```php
Gate::authorize('view-profit', $branch);
```

Proyek tidak menggunakan `Gate::before`. Kemampuan Owner dinyatakan eksplisit pada setiap Gate dan Policy agar validasi cabang/model tetap terlihat dan tidak terlewati.

## 10. Aturan branch_id dan mass assignment

- Admin dan Kasir tidak boleh menentukan `branch_id` dari request.
- Controller atau service harus menggunakan `auth()->user()->branch_id`.
- Nilai query string atau hidden input yang berbeda harus diabaikan atau ditolak.
- Owner boleh memilih cabang, tetapi ID harus divalidasi sebagai cabang aktif yang tersedia.
- `BranchAccessService::resolveBranchId()` menjadi fondasi untuk aturan ini saat operasi bisnis dibuat.

## 10A. Pengelolaan password

- Hanya Owner aktif yang dapat membuka `/account/password`.
- Owner dapat menetapkan password baru untuk akun Owner, Admin, dan Kasir.
- Owner wajib memasukkan password Owner saat ini sebagai konfirmasi.
- Admin dan Kasir tidak dapat mengubah password sendiri maupun password pengguna lain.
- Pembatasan diterapkan melalui middleware `role:owner`, `UserPolicy::resetPassword()`, dan authorization pada Form Request.
- Password lama tidak pernah ditampilkan karena database hanya menyimpan hash.
- Password baru harus dikonfirmasi, memenuhi aturan kekuatan password, dan berbeda dari password akun target saat ini.
- Fitur lupa password hanya mengirim token untuk akun Owner aktif.
- Token reset milik Admin/Kasir ditolak; mereka harus meminta Owner menetapkan password baru.

## 11. Pencegahan manipulasi URL

Route cabang memakai route model binding, kemudian `branch.access`. Admin/Kasir yang mengganti `{branch}` ke cabang lain memperoleh `404` tanpa data cabang tujuan. Query parameter `branch_id` tidak dibaca oleh middleware maupun scope.

Perbedaan respons:

- `403` untuk pengguna terautentikasi yang role atau ability-nya tidak diizinkan.
- `404` untuk akses langsung ke ID cabang lain, guna mengurangi konfirmasi keberadaan resource.
- Guest tetap diarahkan ke login.

Halaman `403` menampilkan pesan Bahasa Indonesia yang umum dan tidak memuat nama Policy, ability internal, stack trace, atau data sensitif.

## 12. Struktur menu

Owner:

`Dashboard`, `Kasir`, `Nota`, `Produk`, `Stok`, `Pengeluaran`, `Laporan`, `Cabang`, `Pengguna`, `Aktivitas`, `Pengaturan`, `Akun Saya`.

Admin:

`Dashboard Cabang`, `Kasir`, `Nota Cabang`, `Produk`, `Stok Cabang`, `Pengeluaran Cabang`, `Laporan Cabang`, `Pegawai Cabang`, `Akun Saya`.

Kasir:

`Transaksi Baru`, `Transaksi Saya`, `Cetak Ulang Nota`, `Akun Saya`.

Menu bisnis yang belum tersedia ditampilkan nonaktif dengan label “Segera” dan tidak diberi URL palsu. Sidebar hanya lapisan presentasi; backend tetap menjadi pengaman utama.

## 13. Menambahkan route baru yang aman

1. Gunakan `auth` dan `active.user`.
2. Tambahkan `role` jika seluruh route dibatasi role tertentu.
3. Gunakan route model binding dan `branch.access` jika URI memuat cabang.
4. Di controller, panggil Gate atau Policy.
5. Terapkan `accessibleTo()` sebelum `get()`, `first()`, pagination, agregasi, atau perhitungan.
6. Ambil cabang Admin/Kasir dari akun, bukan request.

Contoh:

```php
Route::get('/contoh/{branch}', $handler)
    ->middleware(['auth', 'active.user', 'role:owner,admin', 'branch.access']);
```

## 14. Pemeriksaan lokal

Route berikut hanya diregistrasikan ketika environment `local` atau `testing`:

| Method | URI | Nama | Perlindungan tambahan |
| --- | --- | --- | --- |
| GET | `/authorization-check` | `authorization-check.index` | — |
| GET | `/authorization-check/owner` | `authorization-check.owner` | `role:owner` |
| GET | `/authorization-check/management` | `authorization-check.management` | `role:owner,admin` |
| GET | `/authorization-check/cashier` | `authorization-check.cashier` | `role:owner,admin,cashier` |
| GET | `/authorization-check/branches/{branch}` | `authorization-check.branch` | `branch.access` |
| GET | `/authorization-check/profit/{branch}` | `authorization-check.profit` | Gate `view-profit` |

Semua route juga memakai `auth` dan `active.user`. Controller mempunyai pemeriksaan environment tambahan sehingga hasil tetap `404` bila environment bukan `local`/`testing`.

Halaman utama pemeriksaan menampilkan role, cabang, matriks ability, cabang yang terlihat, serta jumlah sampel sale dan expense yang sudah dibatasi scope. Halaman tidak menampilkan nilai laba atau data rahasia.

## 15. Cara menguji manual

1. Gunakan environment lokal dan jalankan aplikasi.
2. Login sebagai Owner aktif, buka `/authorization-check`, cabang A/B, dan route profit A/B.
3. Login sebagai Admin cabang A, pastikan hanya cabang A terlihat.
4. Ganti ID URL cabang dan profit ke cabang B; akses cabang harus `404`, sedangkan Gate profit cabang lain harus `403`.
5. Tambahkan `?branch_id=<id-cabang-lain>`; cakupan tidak boleh berubah.
6. Login sebagai Kasir cabang A; pastikan sale yang terlihat hanya miliknya, expense berjumlah nol, dan route profit menghasilkan `403`.
7. Buka route Owner sebagai Admin/Kasir; hasil harus `403`.
8. Pastikan halaman `403`, `/system-check`, `/design-system`, login, logout, dan `/account` tetap berjalan tanpa error browser maupun Laravel.

Skenario hidden input diuji melalui service: nilai cabang yang diminta Admin/Kasir diabaikan dan hasil selalu cabang akun.

## 16. Automated test

Test modular tersedia di `tests/Feature/Authorization`:

- `RoleMiddlewareTest.php`
- `BranchAccessMiddlewareTest.php`
- `AuthorizationGateTest.php`
- `PolicyTest.php`
- `QueryScopeAccessTest.php`
- `MenuVisibilityTest.php`
- `AuthorizationUrlManipulationTest.php`

`AuthorizationTestCase.php` menyediakan helper fixture pengujian. Factory `ExpenseCategoryFactory` dan `ExpenseFactory` ditambahkan hanya untuk membuat data test otorisasi.

Test mencakup guest, akun nonaktif, slug role palsu, route Owner, isolasi dua cabang, query parameter, nilai cabang request, seluruh Gate, Policy utama, local scope, menu per role, keamanan halaman `403`, route production, serta regresi Tahap 1–4.

## 17. Batas tahap

- CRUD cabang dan pengguna direncanakan pada Tahap 6.
- Laporan laba sebenarnya belum dibuat pada Tahap 5.
- Tampilan difokuskan pada desktop/laptop 1024–1920 piksel.
- Tidak ada package permission eksternal, global scope, API bisnis, AJAX bisnis, atau route bisnis palsu.

## 18. Masalah yang ditemukan dan penyelesaian

- Proyek belum memiliki middleware role/cabang, service akses cabang, Gate, Policy, dan scope eksplisit. Komponen tersebut ditambahkan dengan kemampuan bawaan Laravel.
- Sidebar sebelumnya belum membedakan role. Satu partial yang sama kini memilih matriks menu dari role database dan menonaktifkan modul yang belum ada.
- Halaman penolakan khusus belum tersedia. View `errors/403.blade.php` dan asset modular ditambahkan tanpa menampilkan detail internal.
- Data cabang sebelumnya belum memiliki pola pembatasan query reusable. Local scope eksplisit ditambahkan; global scope sengaja tidak digunakan.
- Pemeriksaan format awal menemukan perbedaan gaya pada file yang disentuh. Laravel Pint memperbaikinya dan seluruh test dijalankan kembali.

Tidak ada credential, token, APP_KEY, session ID, atau informasi produksi sensitif di dokumentasi ini.
