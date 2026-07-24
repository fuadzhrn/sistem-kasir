<section class="ds-section" id="loading-state" aria-labelledby="loading-state-title">
    <div class="ds-section__header">
        <div>
            <span class="ds-section__number">13</span>
            <h2 id="loading-state-title">Loading state</h2>
            <p>Indikator proses berupa spinner, skeleton card, skeleton row, dan tombol loading.</p>
        </div>
    </div>

    <div class="loading-demo">
        <div class="loading-demo__indicators">
            <div class="spinner" role="status">
                <span class="visually-hidden">Memuat</span>
            </div>
            <button class="btn btn-primary is-loading" type="button" disabled>Memuat</button>
        </div>

        <div class="loading-demo__skeletons">
            <div class="skeleton-card" aria-label="Contoh skeleton card">
                <div class="skeleton skeleton-line skeleton-line--title"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line skeleton-line--short"></div>
            </div>
            <div class="skeleton-table-row" aria-label="Contoh skeleton baris tabel">
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line"></div>
                <div class="skeleton skeleton-line"></div>
            </div>
        </div>
    </div>
</section>
