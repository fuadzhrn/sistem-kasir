<form class="card activities-filters" method="GET" action="{{ route('activities.index') }}" data-activity-filters>
    <div class="activities-filters__header">
        <div>
            <p class="eyebrow">Penyaringan data</p>
            <h2>Filter aktivitas</h2>
            <p>Persempit riwayat berdasarkan pengguna, modul, tindakan, atau waktu.</p>
        </div>
        @if (count(array_filter($filters, fn ($value) => filled($value))) > 0)
            <span class="badge badge-info">Filter aktif</span>
        @endif
    </div>

    <div class="activities-filters__grid">
        <label class="form-group activities-filters__search">
            <span class="form-label">Cari aktivitas</span>
            <input class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Deskripsi, pengguna, cabang, atau ID referensi">
        </label>

        @if ($viewer->isOwner())
            <label class="form-group">
                <span class="form-label">Cabang</span>
                <select class="form-select" name="branch">
                    <option value="">Semua cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch'] ?? '') === (string) $branch->id)>
                            {{ $branch->code }} — {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="form-group">
            <span class="form-label">Pengguna</span>
            <select class="form-select" name="user">
                <option value="">Semua pengguna</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) ($filters['user'] ?? '') === (string) $user->id)>
                        {{ $user->name }} ({{ $user->username }})
                    </option>
                @endforeach
            </select>
        </label>

        <label class="form-group">
            <span class="form-label">Modul</span>
            <select class="form-select" name="module">
                <option value="">Semua modul</option>
                @foreach ($modules as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['module'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="form-group">
            <span class="form-label">Aksi</span>
            <select class="form-select" name="action">
                <option value="">Semua aksi</option>
                @foreach ($actionOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="form-group">
            <span class="form-label">Dari tanggal</span>
            <input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" max="{{ now()->toDateString() }}">
        </label>

        <label class="form-group">
            <span class="form-label">Sampai tanggal</span>
            <input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" max="{{ now()->toDateString() }}">
        </label>

        @if ($viewer->isOwner())
            <label class="form-group">
                <span class="form-label">Alamat IP</span>
                <input class="form-control" type="text" name="ip" value="{{ $filters['ip'] ?? '' }}" maxlength="45" placeholder="Contoh: 127.0.0.1">
            </label>
        @endif

        <label class="form-group">
            <span class="form-label">Data per halaman</span>
            <select class="form-select" name="per_page">
                @foreach ([25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>{{ $size }} data</option>
                @endforeach
            </select>
        </label>
    </div>
    <div class="activities-filters__actions">
        <a class="btn btn-secondary" href="{{ route('activities.index') }}">Reset filter</a>
        <button class="btn btn-primary" type="submit">Terapkan filter</button>
    </div>
</form>
