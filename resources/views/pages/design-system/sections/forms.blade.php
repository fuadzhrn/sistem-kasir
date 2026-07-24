<section class="ds-section" id="forms" aria-labelledby="forms-title">
    <div class="ds-section__header">
        <div>
            <span class="ds-section__number">05</span>
            <h2 id="forms-title">Form</h2>
            <p>Kontrol input semantik dengan label, bantuan, validasi visual, dan focus state hijau.</p>
        </div>
    </div>

    <form class="demo-form" data-demo-form action="#" method="get" autocomplete="off">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="demo-name">Nama produk <span class="form-required">*</span></label>
                <input class="form-control" id="demo-name" name="demo_name" type="text" placeholder="Contoh: Pupuk Organik">
                <small class="form-help">Masukkan nama yang mudah dikenali.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-number">Jumlah</label>
                <input class="form-control is-success" id="demo-number" name="demo_number" type="number" value="24" min="0">
                <small class="form-success">Nilai contoh valid.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-password">Password contoh</label>
                <input class="form-control" id="demo-password" name="demo_password" type="password" value="contoh123">
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-search">Pencarian</label>
                <div class="search-control">
                    <svg class="search-control__icon" viewBox="0 0 20 20" aria-hidden="true">
                        <circle cx="8.5" cy="8.5" r="5.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
                        <path d="m13 13 4 4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.6"/>
                    </svg>
                    <input class="form-control" id="demo-search" name="demo_search" type="search" placeholder="Cari data contoh">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-select">Kategori</label>
                <select class="form-select" id="demo-select" name="demo_select">
                    <option value="">Pilih kategori</option>
                    <option>Pupuk</option>
                    <option>Benih</option>
                    <option>Peralatan</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-error">Kode contoh</label>
                <input class="form-control is-error" id="demo-error" name="demo_error" type="text" value="ABC" aria-describedby="demo-error-message">
                <small class="form-error" id="demo-error-message">Format kode contoh belum sesuai.</small>
            </div>

            <div class="form-group form-group--full">
                <label class="form-label" for="demo-description">Keterangan</label>
                <textarea class="form-textarea" id="demo-description" name="demo_description" placeholder="Tulis keterangan singkat"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-price">Input group</label>
                <div class="input-group">
                    <span class="input-group__addon">Rp</span>
                    <input class="form-control" id="demo-price" name="demo_price" type="number" value="125000">
                    <span class="input-group__addon">/unit</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-file">File contoh</label>
                <input class="form-file" id="demo-file" name="demo_file" type="file">
            </div>

            <div class="form-group">
                <span class="form-label">Pilihan</span>
                <div class="form-check-group">
                    <label class="form-check" for="demo-checkbox">
                        <input id="demo-checkbox" name="demo_checkbox" type="checkbox" checked>
                        <span>Checkbox</span>
                    </label>
                    <label class="form-check" for="demo-radio-a">
                        <input id="demo-radio-a" name="demo_radio" type="radio" checked>
                        <span>Pilihan A</span>
                    </label>
                    <label class="form-check" for="demo-radio-b">
                        <input id="demo-radio-b" name="demo_radio" type="radio">
                        <span>Pilihan B</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="demo-disabled">Disabled dan readonly</label>
                <div class="form-inline-fields">
                    <input class="form-control" id="demo-disabled" type="text" value="Tidak dapat diubah" disabled>
                    <label class="visually-hidden" for="demo-readonly">Readonly</label>
                    <input class="form-control" id="demo-readonly" type="text" value="Hanya dibaca" readonly>
                </div>
            </div>
        </div>
    </form>
</section>
