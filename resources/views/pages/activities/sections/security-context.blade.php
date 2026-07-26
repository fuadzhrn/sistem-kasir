<section class="card activities-detail-card">
    <h2>Konteks keamanan</h2>
    @if ($viewer->isOwner())
        <dl class="activities-definition-list">
            <div><dt>Alamat IP</dt><dd>{{ $activity['ip_address'] ?: 'Tidak tersedia' }}</dd></div>
            <div class="activities-definition-list__wide"><dt>User agent</dt><dd class="activities-break-text">{{ $activity['user_agent'] ?: 'Tidak tersedia' }}</dd></div>
        </dl>
    @else
        <p class="activities-muted">Informasi IP dan user agent hanya dapat dilihat oleh Owner.</p>
    @endif
</section>
