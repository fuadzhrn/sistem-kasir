@php
    $selectedAdjustmentBranch = auth()->user()->isOwner() && filled($filters['branch_id'] ?? null)
        ? $branches->firstWhere('id', (int) $filters['branch_id'])
        : null;
    $selectedAdjustmentProduct = filled($filters['product_id'] ?? null)
        ? $products->firstWhere('id', (int) $filters['product_id'])
        : null;
    $selectedAdjustmentUser = filled($filters['user_id'] ?? null)
        ? $users->firstWhere('id', (int) $filters['user_id'])
        : null;
    $selectedAdjustmentType = $labels[$filters['adjustment_type'] ?? ''] ?? null;
@endphp

<section class="card adjustment-filter-card" aria-label="Pencarian dan filter penyesuaian stok">
    <form action="{{ route('stock-adjustments.index') }}" method="GET" class="adjustment-filter-grid">
        <div class="form-group adjustment-filter-grid__search">
            <label class="form-label" for="adjustment-search">Nomor atau produk</label>
            <input class="form-control" id="adjustment-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Nomor, kode, atau nama produk">
        </div>
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="adjustment-branch-filter">Cabang</label>
                <select class="form-select" id="adjustment-branch-filter" name="branch_id">
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
            <label class="form-label" for="adjustment-type-filter">Jenis</label>
            <select class="form-select" id="adjustment-type-filter" name="adjustment_type">
                <option value="">Semua jenis</option>
                @foreach ($labels as $type => $label)
                    <option value="{{ $type }}" @selected(($filters['adjustment_type'] ?? '') === $type)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="adjustment-product-filter">Produk</label>
            <select class="form-select" id="adjustment-product-filter" name="product_id">
                <option value="">Semua produk</option>
                @foreach ($products as $filterProduct)
                    <option value="{{ $filterProduct->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $filterProduct->id)>
                        {{ $filterProduct->code }} - {{ $filterProduct->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="adjustment-user-filter">Dibuat oleh</label>
            <select class="form-select" id="adjustment-user-filter" name="user_id">
                <option value="">Semua pengguna</option>
                @foreach ($users as $filterUser)
                    <option value="{{ $filterUser->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $filterUser->id)>{{ $filterUser->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="adjustment-date-from">Dari tanggal</label>
            <input class="form-control" id="adjustment-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="adjustment-date-to">Sampai tanggal</label>
            <input class="form-control" id="adjustment-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="adjustment-filter-grid__actions">
            <a class="btn btn-secondary" href="{{ route('stock-adjustments.index') }}">Reset</a>
            <button class="btn btn-primary" type="submit">Terapkan Filter</button>
        </div>
    </form>

    @if (
        filled($filters['search'] ?? null)
        || $selectedAdjustmentBranch
        || $selectedAdjustmentProduct
        || $selectedAdjustmentUser
        || $selectedAdjustmentType
        || filled($filters['date_from'] ?? null)
        || filled($filters['date_to'] ?? null)
    )
        <div class="stock-adjustments-filter-summary" aria-label="Filter aktif">
            <span class="stock-adjustments-filter-summary__label">Filter aktif:</span>
            <div class="stock-adjustments-filter-summary__items">
                @if (filled($filters['search'] ?? null))
                    <span>Pencarian “{{ $filters['search'] }}”</span>
                @endif
                @if ($selectedAdjustmentBranch)
                    <span>Cabang {{ $selectedAdjustmentBranch->name }}</span>
                @endif
                @if ($selectedAdjustmentProduct)
                    <span>Produk {{ $selectedAdjustmentProduct->name }}</span>
                @endif
                @if ($selectedAdjustmentType)
                    <span>Jenis {{ $selectedAdjustmentType }}</span>
                @endif
                @if ($selectedAdjustmentUser)
                    <span>Pengguna {{ $selectedAdjustmentUser->name }}</span>
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
