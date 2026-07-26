<div class="table-wrapper activities-table-wrapper">
    <table class="table activities-table">
        <thead>
            <tr>
                <th class="activities-table__time">Waktu</th>
                <th class="activities-table__user">Pengguna</th>
                <th class="activities-table__role">Role</th>
                <th class="activities-table__branch">Cabang</th>
                <th>Modul</th>
                <th>Aksi</th>
                <th>Deskripsi</th>
                <th>Referensi</th>
                @if ($viewer->isOwner())<th>IP</th>@endif
                <th class="activities-table__action"><span class="visually-hidden">Detail</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($activityLogs as $activity)
                <tr>
                    <td class="activities-table__time">
                        <time datetime="{{ $activity['created_at']?->toIso8601String() }}">
                            <strong>{{ $activity['created_at']?->format('d/m/Y') }}</strong>
                            <span>{{ $activity['created_at']?->format('H:i:s') }}</span>
                        </time>
                    </td>
                    <td class="activities-table__user">
                        <strong>{{ $activity['user']?->name ?? 'Sistem/tidak dikenal' }}</strong>
                        <span>{{ $activity['user']?->username ? '@'.$activity['user']->username : 'Tanpa akun aktif' }}</span>
                    </td>
                    <td class="activities-table__role">{{ $activity['user']?->role?->name ?? '—' }}</td>
                    <td class="activities-table__branch">
                        <strong>{{ $activity['branch']?->code ?? 'GLOBAL' }}</strong>
                        <span>{{ $activity['branch']?->name ?? 'Aktivitas Owner' }}</span>
                    </td>
                    <td>@include('pages.activities.sections.module-badge')</td>
                    <td>@include('pages.activities.sections.action-badge')</td>
                    <td class="activities-table__description">{{ $activity['description'] ?: '—' }}</td>
                    <td>{{ $activity['reference_id'] ? '#'.$activity['reference_id'] : '—' }}</td>
                    @if ($viewer->isOwner())<td>{{ $activity['ip_address'] ?: '—' }}</td>@endif
                    <td class="activities-table__action"><a class="btn btn-secondary btn-sm" href="{{ route('activities.show', $activity['id']) }}">Detail</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
