<section class="card transfer-filter-card" aria-label="Filter mutasi stok">
    <form action="{{ route('stock-transfers.index') }}" method="GET" class="transfer-filter-grid">
        <div class="form-group transfer-filter-grid__search">
            <label class="form-label" for="transfer-search">Nomor atau produk</label>
            <input class="form-control" id="transfer-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Nomor, kode, atau nama produk">
        </div>
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="transfer-branch-filter">Cabang terkait</label>
                <select class="form-select" id="transfer-branch-filter" name="branch_id">
                    <option value="">Semua cabang</option>
                    @foreach ($branches as $filterBranch)
                        <option value="{{ $filterBranch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $filterBranch->id)>
                            {{ $filterBranch->code }} - {{ $filterBranch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="form-group">
            <label class="form-label" for="transfer-status-filter">Status</label>
            <select class="form-select" id="transfer-status-filter" name="status">
                <option value="">Semua status</option>
                @foreach ($labels as $status => $label)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="transfer-product-filter">Produk</label>
            <select class="form-select" id="transfer-product-filter" name="product_id">
                <option value="">Semua produk</option>
                @foreach ($products as $filterProduct)
                    <option value="{{ $filterProduct->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $filterProduct->id)>
                        {{ $filterProduct->code }} - {{ $filterProduct->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="transfer-date-from">Dari tanggal</label>
            <input class="form-control" id="transfer-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="transfer-date-to">Sampai tanggal</label>
            <input class="form-control" id="transfer-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="transfer-filter-grid__actions">
            <a class="btn btn-secondary" href="{{ route('stock-transfers.index') }}">Reset</a>
            <button class="btn btn-primary" type="submit">Terapkan Filter</button>
        </div>
    </form>
</section>
