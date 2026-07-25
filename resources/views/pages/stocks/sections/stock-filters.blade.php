<section class="card stock-filter-card" aria-label="Filter stok">
    <form class="stock-filters" method="GET" action="{{ route('stocks.index') }}">
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="stock-branch">Cabang</label>
                <select class="form-select" id="stock-branch" name="branch_id" required>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($selectedBranch->is($branch))>
                            {{ $branch->code }} — {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="form-group stock-filters__search">
            <label class="form-label" for="stock-search">Pencarian</label>
            <input
                class="form-control"
                id="stock-search"
                name="search"
                type="search"
                maxlength="100"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Kode, barcode, atau nama produk"
            >
        </div>
        <div class="form-group">
            <label class="form-label" for="stock-category">Kategori</label>
            <select class="form-select" id="stock-category" name="category_id">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="stock-status">Status</label>
            <select class="form-select" id="stock-status" name="status">
                <option value="">Semua status</option>
                <option value="safe" @selected(($filters['status'] ?? '') === 'safe')>Aman</option>
                <option value="low" @selected(($filters['status'] ?? '') === 'low')>Menipis</option>
                <option value="out" @selected(($filters['status'] ?? '') === 'out')>Habis</option>
            </select>
        </div>
        <div class="stock-filter-actions">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a
                class="btn btn-secondary"
                href="{{ route('stocks.index', auth()->user()->isOwner() ? ['branch_id' => $selectedBranch->id] : []) }}"
            >
                Reset
            </a>
        </div>
    </form>
</section>
