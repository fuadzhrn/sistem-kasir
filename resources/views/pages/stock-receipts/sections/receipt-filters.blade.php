@php
    $selectedReceiptBranch = auth()->user()->isOwner() && filled($filters['branch_id'] ?? null)
        ? $branches->firstWhere('id', (int) $filters['branch_id'])
        : null;
@endphp

<section class="card receipt-filter-card" aria-label="Pencarian dan filter barang masuk">
    <form action="{{ route('stock-receipts.index') }}" method="GET" class="receipt-filter-grid">
        <div class="form-group receipt-filter-grid__search">
            <label class="form-label" for="receipt-search">Nomor atau supplier</label>
            <input
                class="form-control"
                id="receipt-search"
                name="search"
                type="search"
                value="{{ $filters['search'] ?? '' }}"
                maxlength="100"
                placeholder="Cari nomor penerimaan atau supplier"
            >
        </div>
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="receipt-branch-filter">Cabang</label>
                <select class="form-select" id="receipt-branch-filter" name="branch_id">
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
            <label class="form-label" for="receipt-date-from">Dari tanggal</label>
            <input class="form-control" id="receipt-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="receipt-date-to">Sampai tanggal</label>
            <input class="form-control" id="receipt-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="receipt-supplier-filter">Supplier</label>
            <input class="form-control" id="receipt-supplier-filter" name="supplier" type="search" value="{{ $filters['supplier'] ?? '' }}" maxlength="150" placeholder="Nama supplier">
        </div>
        <div class="receipt-filter-grid__actions">
            <a class="btn btn-secondary" href="{{ route('stock-receipts.index') }}">Reset</a>
            <button class="btn btn-primary" type="submit">Terapkan Filter</button>
        </div>
    </form>

    @if (
        filled($filters['search'] ?? null)
        || $selectedReceiptBranch
        || filled($filters['date_from'] ?? null)
        || filled($filters['date_to'] ?? null)
        || filled($filters['supplier'] ?? null)
    )
        <div class="goods-receipts-filter-summary" aria-label="Filter aktif">
            <span class="goods-receipts-filter-summary__label">Filter aktif:</span>
            <div class="goods-receipts-filter-summary__items">
                @if (filled($filters['search'] ?? null))
                    <span>Pencarian “{{ $filters['search'] }}”</span>
                @endif
                @if ($selectedReceiptBranch)
                    <span>Cabang {{ $selectedReceiptBranch->name }}</span>
                @endif
                @if (filled($filters['supplier'] ?? null))
                    <span>Supplier {{ $filters['supplier'] }}</span>
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
