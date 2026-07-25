<section class="card filter-card">
    <form class="module-filters" action="{{ route('branches.index') }}" method="GET">
        <div class="form-group module-filters__search">
            <label class="form-label" for="search">Pencarian</label>
            <input class="form-control" id="search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Kode, nama, atau telepon">
        </div>
        <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="module-filters__actions">
            <button class="btn btn-secondary" type="submit">Terapkan</button>
            <a class="btn btn-ghost" href="{{ route('branches.index') }}">Reset</a>
            <a class="btn btn-primary" href="{{ route('branches.create') }}">Tambah Cabang</a>
        </div>
    </form>
</section>
