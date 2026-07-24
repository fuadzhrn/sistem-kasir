<section class="ds-section" id="tables" aria-labelledby="tables-title">
    <div class="ds-section__header">
        <div>
            <span class="ds-section__number">08</span>
            <h2 id="tables-title">Tabel desktop</h2>
            <p>Tabel data statis dengan toolbar, alignment angka, badge status, aksi disabled, dan pagination visual.</p>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-control">
                <label class="visually-hidden" for="table-search">Cari produk contoh</label>
                <svg class="search-control__icon" viewBox="0 0 20 20" aria-hidden="true">
                    <circle cx="8.5" cy="8.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
                    <path d="m13 13 4 4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"/>
                </svg>
                <input class="form-control" id="table-search" type="search" placeholder="Cari produk contoh">
            </div>
            <div class="table-toolbar__controls">
                <label class="visually-hidden" for="table-filter">Filter kategori</label>
                <select class="form-select" id="table-filter">
                    <option>Semua kategori</option>
                    <option>Pupuk</option>
                    <option>Benih</option>
                </select>
                <button class="btn btn-secondary" type="button" disabled>Filter lanjutan</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Produk</th>
                        <th scope="col">Kategori</th>
                        <th class="table-number" scope="col">Harga</th>
                        <th class="table-number" scope="col">Stok</th>
                        <th scope="col">Status</th>
                        <th class="table-actions" scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>PRD-001</td>
                        <td><strong>Pupuk Organik Contoh</strong></td>
                        <td>Pupuk</td>
                        <td class="table-number">Rp75.000</td>
                        <td class="table-number">48</td>
                        <td><span class="badge badge-success">Aktif</span></td>
                        <td class="table-actions"><button class="btn btn-ghost btn-sm" type="button" disabled>Detail</button></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>PRD-002</td>
                        <td><strong>Benih Jagung Contoh</strong></td>
                        <td>Benih</td>
                        <td class="table-number">Rp125.000</td>
                        <td class="table-number">6</td>
                        <td><span class="badge badge-warning">Menipis</span></td>
                        <td class="table-actions"><button class="btn btn-ghost btn-sm" type="button" disabled>Detail</button></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>PRD-003</td>
                        <td><strong>Alat Semprot Contoh</strong></td>
                        <td>Peralatan</td>
                        <td class="table-number">Rp350.000</td>
                        <td class="table-number">0</td>
                        <td><span class="badge badge-danger">Habis</span></td>
                        <td class="table-actions"><button class="btn btn-ghost btn-sm" type="button" disabled>Detail</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <span>Menampilkan 1–3 dari 3 data contoh</span>
            <div class="pagination-buttons" aria-label="Pagination contoh">
                <button class="pagination-button" type="button" disabled aria-label="Halaman sebelumnya">‹</button>
                <button class="pagination-button is-active" type="button" aria-current="page">1</button>
                <button class="pagination-button" type="button" disabled aria-label="Halaman berikutnya">›</button>
            </div>
        </div>
    </div>

    <div class="table-empty-demo">
        <h3>Empty row</h3>
        <div class="table-card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-empty-row">
                            <td colspan="3">Belum ada data untuk ditampilkan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
