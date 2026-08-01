<section class="card activities-detail-card activities-detail-card--wide activity-change" aria-labelledby="activity-change-title">
    <div class="activities-detail-section-heading">
        <div>
            <p class="eyebrow">Perbandingan</p>
            <h2 id="activity-change-title">Data Sebelum dan Sesudah</h2>
        </div>
    </div>

    @if ($hasChangeData)
        <div class="activity-change__grid">
            <article class="activity-change__section activity-change__before">
                <h3>Data Sebelum</h3>
                @if ($beforeData === [])
                    <p class="activities-muted">Tidak ada data sebelum yang tercatat.</p>
                @else
                    @include('pages.activities.sections.metadata-items', ['items' => $beforeData])
                @endif
            </article>
            <article class="activity-change__section activity-change__after">
                <h3>Data Sesudah</h3>
                @if ($afterData === [])
                    <p class="activities-muted">Tidak ada data sesudah yang tercatat.</p>
                @else
                    @include('pages.activities.sections.metadata-items', ['items' => $afterData])
                @endif
            </article>
        </div>
    @else
        <div class="activity-change__empty" role="status">
            <strong>Tidak ada perubahan sebelum/sesudah</strong>
            <p>Aktivitas ini tidak menyimpan pasangan data perbandingan.</p>
        </div>
    @endif
</section>
