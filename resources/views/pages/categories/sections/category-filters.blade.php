<section class="card filter-card" aria-label="Filter kategori">
    <form class="module-filters" method="GET" action="{{ route('categories.index') }}">
        <div class="form-group">
            <label class="form-label" for="category-search">Pencarian</label>
            <input class="form-control" id="category-search" name="search" type="search" maxlength="100" value="{{ $search }}" placeholder="Nama, slug, atau deskripsi">
        </div>
        <div class="form-group">
            <label class="form-label" for="category-status">Status</label>
            <select class="form-control" id="category-status" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="module-filters__actions">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-secondary" href="{{ route('categories.index') }}">Reset</a>
        </div>
    </form>
</section>
