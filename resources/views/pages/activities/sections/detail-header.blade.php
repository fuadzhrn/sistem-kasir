<header class="card activities-detail-header">
    <div>
        <a class="activities-back-link" href="{{ route('activities.index') }}">← Kembali ke Audit Aktivitas</a>
        <div class="activities-detail-header__badges">
            @include('pages.activities.sections.module-badge')
            @include('pages.activities.sections.action-badge')
        </div>
        <h1>{{ $activity['action_label'] }}</h1>
        <p>{{ $activity['description'] ?: 'Tidak ada deskripsi tambahan.' }}</p>
    </div>
    <div class="activities-detail-header__time">
        <span>Waktu aktivitas</span>
        <strong>{{ $activity['created_at']?->locale('id')->translatedFormat('d F Y') }}</strong>
        <span>{{ $activity['created_at']?->format('H.i.s') }}</span>
        <small>Zona waktu aplikasi</small>
    </div>
</header>
