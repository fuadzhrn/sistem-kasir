<form class="card expense-category-filters" method="GET" action="{{ route('expense-categories.index') }}">
    <div class="form-group">
        <label class="form-label" for="category-search">Pencarian</label>
        <input class="form-control" id="category-search" name="search" value="{{ $search }}" maxlength="100" placeholder="Nama, slug, atau deskripsi">
    </div>
    <div class="form-group">
        <label class="form-label" for="category-status">Status</label>
        <select class="form-select" id="category-status" name="status">
            <option value="">Semua status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>
    </div>
    <div class="expense-filter-actions">
        <button class="btn btn-primary" type="submit">Terapkan</button>
        <a class="btn btn-secondary" href="{{ route('expense-categories.index') }}">Reset</a>
    </div>
</form>
