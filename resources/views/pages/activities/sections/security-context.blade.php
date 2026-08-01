<section class="card activities-detail-card activities-detail-card--wide activities-technical">
    <details data-activity-technical>
        <summary aria-expanded="false">
            <span>
                <strong>Informasi Teknis</strong>
                <small>IP, user agent, dan konteks keamanan</small>
            </span>
            <span aria-hidden="true" data-activity-technical-icon>+</span>
        </summary>
        <div class="activities-technical__content">
            @if ($viewer->isOwner())
                <dl class="activities-definition-list">
                    <div><dt>Alamat IP</dt><dd>{{ $activity['ip_address'] ?: 'Tidak tersedia' }}</dd></div>
                    <div class="activities-definition-list__wide"><dt>User agent</dt><dd class="activities-break-text">{{ $activity['user_agent'] ?: 'Tidak tersedia' }}</dd></div>
                </dl>
            @else
                <p class="activities-muted">Informasi IP dan user agent hanya dapat dilihat oleh Owner.</p>
            @endif
        </div>
    </details>
</section>
