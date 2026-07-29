@php
    $historySelectedBranch = filled($filters['branch_id'] ?? null)
        ? $branches->firstWhere('id', (int) $filters['branch_id'])
        : null;
    $historySelectedProduct = filled($filters['product_id'] ?? null)
        ? $products->firstWhere('id', (int) $filters['product_id'])
        : null;
    $historySelectedCategory = filled($filters['category_id'] ?? null)
        ? $categories->firstWhere('id', (int) $filters['category_id'])
        : null;
    $historySelectedUser = filled($filters['user_id'] ?? null)
        ? $users->firstWhere('id', (int) $filters['user_id'])
        : null;
    $historySelectedMovement = $movementLabels[$filters['movement_type'] ?? ''] ?? null;
@endphp

<section class="card stock-history-filter-card" aria-label="Pencarian dan filter riwayat stok">
    <form class="stock-history-filters" method="GET" action="{{ route('stocks.history.index') }}">
        <div class="form-group stock-history-filters__search">
            <label class="form-label" for="history-search">Pencarian</label>
            <input class="form-control" id="history-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau kode produk">
        </div>
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="history-branch">Cabang</label>
                <select class="form-select" id="history-branch" name="branch_id">
                    <option value="">Semua cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>
                            {{ $branch->code }} — {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="form-group">
            <label class="form-label" for="history-product">Produk</label>
            <select class="form-select" id="history-product" name="product_id">
                <option value="">Semua produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((int) ($filters['product_id'] ?? 0) === $product->id)>
                        {{ $product->code }} — {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="history-category">Kategori</label>
            <select class="form-select" id="history-category" name="category_id">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="history-type">Jenis Perubahan</label>
            <select class="form-select" id="history-type" name="movement_type">
                <option value="">Semua jenis</option>
                @foreach ($movementLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['movement_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="history-user">Dilakukan Oleh</label>
            <select class="form-select" id="history-user" name="user_id">
                <option value="">Semua pengguna</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="history-from">Tanggal Mulai</label>
            <input class="form-control" id="history-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="history-to">Tanggal Selesai</label>
            <input class="form-control" id="history-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="stock-filter-actions">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-secondary" href="{{ route('stocks.history.index') }}">Reset</a>
        </div>
    </form>

    @if (
        filled($filters['search'] ?? null)
        || $historySelectedBranch
        || $historySelectedProduct
        || $historySelectedCategory
        || $historySelectedMovement
        || $historySelectedUser
        || filled($filters['date_from'] ?? null)
        || filled($filters['date_to'] ?? null)
    )
        <div class="inventory-filter-summary" aria-label="Filter riwayat aktif">
            <span class="inventory-filter-summary__label">Filter aktif:</span>
            <div class="inventory-filter-summary__items">
                @if (filled($filters['search'] ?? null))
                    <span>Pencarian “{{ $filters['search'] }}”</span>
                @endif
                @if ($historySelectedBranch)
                    <span>Cabang {{ $historySelectedBranch->name }}</span>
                @endif
                @if ($historySelectedProduct)
                    <span>Produk {{ $historySelectedProduct->name }}</span>
                @endif
                @if ($historySelectedCategory)
                    <span>Kategori {{ $historySelectedCategory->name }}</span>
                @endif
                @if ($historySelectedMovement)
                    <span>Jenis {{ $historySelectedMovement }}</span>
                @endif
                @if ($historySelectedUser)
                    <span>Pengguna {{ $historySelectedUser->name }}</span>
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
