<section class="card activities-detail-card">
    <h2>Informasi Pengguna dan Cabang</h2>
    <dl class="activities-definition-list">
        <div><dt>Nama</dt><dd>{{ $activity['user']?->name ?? 'Sistem/tidak dikenal' }}</dd></div>
        <div><dt>Username</dt><dd>{{ $activity['user']?->username ?? '—' }}</dd></div>
        <div><dt>Role</dt><dd>{{ $activity['user']?->role?->name ?? '—' }}</dd></div>
        <div><dt>Cabang audit</dt><dd>{{ $activity['branch'] ? $activity['branch']->code.' — '.$activity['branch']->name : 'Global (Owner)' }}</dd></div>
    </dl>
</section>
