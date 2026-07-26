<header class="card activities-detail-header">
    <div>
        <a class="activities-back-link" href="{{ route('activities.index') }}">← Kembali ke aktivitas</a>
        <p class="eyebrow">{{ $activity['module_label'] }}</p>
        <h1>{{ $activity['action_label'] }}</h1>
        <p>{{ $activity['description'] ?: 'Tidak ada deskripsi tambahan.' }}</p>
    </div>
    <div class="activities-detail-header__time">
        <span>Waktu pemeriksaan</span>
        <strong>{{ $activity['created_at']?->format('d/m/Y H:i:s') }}</strong>
        <small>Zona waktu aplikasi</small>
    </div>
</header>
