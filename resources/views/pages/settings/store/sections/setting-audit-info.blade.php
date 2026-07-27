<section class="card settings-side-card">
    <div class="card__header"><h2>Jejak Perubahan</h2></div>
    <div class="card__body">
        @if ($lastUpdatedSetting)
            <p>Terakhir diperbarui {{ $lastUpdatedSetting->updated_at->locale('id')->diffForHumans() }}.</p>
            <p>Oleh <strong>{{ $lastUpdatedSetting->updater?->name ?? 'Pengguna historis' }}</strong>.</p>
        @else
            <p>Belum ada perubahan pengaturan oleh Owner.</p>
        @endif
        <a class="btn btn-secondary btn-sm" href="{{ route('activities.index', ['module' => 'settings']) }}">Lihat Audit Aktivitas</a>
    </div>
</section>
