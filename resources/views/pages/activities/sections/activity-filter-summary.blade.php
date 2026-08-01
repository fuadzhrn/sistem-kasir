@php
    $filterChips = [
        ['label' => 'Periode', 'value' => $periodLabel],
        ['label' => 'Cabang', 'value' => $branchLabel],
    ];

    if (filled($filters['search'] ?? null)) {
        $filterChips[] = ['label' => 'Pencarian', 'value' => $filters['search']];
    }

    if (filled($filters['user'] ?? null)) {
        $selectedUser = $users->firstWhere('id', (int) $filters['user']);
        $filterChips[] = ['label' => 'Pengguna', 'value' => $selectedUser?->name ?? '#'.$filters['user']];
    }

    if (filled($filters['module'] ?? null)) {
        $filterChips[] = ['label' => 'Modul', 'value' => $modules[$filters['module']] ?? $filters['module']];
    }

    if (filled($filters['action'] ?? null)) {
        $filterChips[] = ['label' => 'Aktivitas', 'value' => $actionOptions[$filters['action']] ?? $filters['action']];
    }

    if ($viewer->isOwner() && filled($filters['ip'] ?? null)) {
        $filterChips[] = ['label' => 'IP', 'value' => $filters['ip']];
    }
@endphp

<section class="activities-filter-summary" aria-label="Filter aktivitas aktif">
    <span>Filter aktif</span>
    <div>
        @foreach ($filterChips as $chip)
            <span class="activities-filter-chip"><strong>{{ $chip['label'] }}:</strong> {{ $chip['value'] }}</span>
        @endforeach
    </div>
</section>
