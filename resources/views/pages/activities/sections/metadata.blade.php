<section class="card activities-detail-card activities-detail-card--wide">
    <h2>Metadata aman</h2>
    @if (empty($activity['metadata']))
        <p class="activities-muted">Tidak ada metadata tambahan untuk aktivitas ini.</p>
    @else
        <div class="activities-metadata">
            @include('pages.activities.sections.metadata-items', ['items' => $activity['metadata']])
        </div>
    @endif
</section>
