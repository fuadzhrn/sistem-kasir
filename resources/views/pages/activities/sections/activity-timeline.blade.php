<div class="activities-timeline" aria-label="Timeline Audit Aktivitas">
    @foreach ($activityLogs as $activity)
        @php
            $actionTone = str_contains($activity['action'], 'failed')
                || str_contains($activity['action'], 'rejected')
                || str_contains($activity['action'], 'cancelled')
                    ? 'danger'
                    : (
                        str_contains($activity['action'], 'created')
                        || str_contains($activity['action'], 'success')
                        || str_contains($activity['action'], 'completed')
                        || str_contains($activity['action'], 'approved')
                            ? 'success'
                            : 'info'
                    );
        @endphp
        <article class="activity-timeline-item activity-timeline-item--{{ $actionTone }}">
            <span class="activity-timeline-item__marker" aria-hidden="true"></span>
            <div class="activity-card">
                <header class="activity-card__header">
                    <div>
                        <span class="activity-card__module">{{ $activity['module_label'] }}</span>
                        <h3>{{ $activity['action_label'] }}</h3>
                        <time datetime="{{ $activity['created_at']?->toIso8601String() }}">
                            {{ $activity['created_at']?->locale('id')->translatedFormat('d F Y') }}
                            <span aria-hidden="true">·</span>
                            {{ $activity['created_at']?->format('H.i') }}
                        </time>
                    </div>
                </header>

                <dl class="activity-card__body">
                    <div><dt>Pengguna</dt><dd>{{ $activity['user']?->name ?? 'Sistem/tidak dikenal' }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $activity['user']?->role?->name ?? '—' }}</dd></div>
                    <div><dt>Cabang</dt><dd>{{ $activity['branch']?->name ?? 'Global (Owner)' }}</dd></div>
                    <div><dt>Referensi</dt><dd>{{ $activity['reference_id'] ? '#'.$activity['reference_id'] : '—' }}</dd></div>
                    <div class="activity-card__summary">
                        <dt>Ringkasan</dt>
                        <dd>{{ $activity['description'] ?: 'Tidak ada deskripsi tambahan.' }}</dd>
                    </div>
                </dl>

                <footer class="activity-card__footer">
                    <a class="btn btn-secondary" href="{{ route('activities.show', $activity['id']) }}">
                        Detail Aktivitas
                    </a>
                </footer>
            </div>
        </article>
    @endforeach
</div>
