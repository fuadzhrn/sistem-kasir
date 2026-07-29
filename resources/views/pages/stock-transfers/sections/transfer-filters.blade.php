@php
    $selectedTransferBranch = auth()->user()->isOwner() && filled($filters['branch_id'] ?? null)
        ? $branches->firstWhere('id', (int) $filters['branch_id'])
        : null;
    $selectedTransferProduct = filled($filters['product_id'] ?? null)
        ? $products->firstWhere('id', (int) $filters['product_id'])
        : null;
    $selectedTransferStatus = $labels[$filters['status'] ?? ''] ?? null;
@endphp

<section class="card transfer-filter-card" aria-label="Pencarian dan filter mutasi stok">
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

    @if (
        filled($filters['search'] ?? null)
        || $selectedTransferBranch
        || $selectedTransferProduct
        || $selectedTransferStatus
        || filled($filters['date_from'] ?? null)
        || filled($filters['date_to'] ?? null)
    )
        <div class="stock-transfers-filter-summary" aria-label="Filter aktif">
            <span class="stock-transfers-filter-summary__label">Filter aktif:</span>
            <div class="stock-transfers-filter-summary__items">
                @if (filled($filters['search'] ?? null))
                    <span>Pencarian “{{ $filters['search'] }}”</span>
                @endif
                @if ($selectedTransferBranch)
                    <span>Cabang terkait {{ $selectedTransferBranch->name }}</span>
                @endif
                @if ($selectedTransferProduct)
                    <span>Produk {{ $selectedTransferProduct->name }}</span>
                @endif
                @if ($selectedTransferStatus)
                    <span>Status {{ $selectedTransferStatus }}</span>
                @endif
                @if (filled($filters['date_from'] ?? null))
                    <span>Mulai {{ $filters['date_from'] }}</span>
                @endif
                @if (filled($filters['date_to'] ?? null))
                    <span>Sampai {{ $filters['date_to'] }}</span>
                @endif
            </div>
        </div>
    @endif
</section>
