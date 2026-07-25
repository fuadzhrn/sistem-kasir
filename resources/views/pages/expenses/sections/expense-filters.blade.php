<section class="card filter-card">
    <form class="module-filters module-filters--expenses" method="GET" action="{{ route('expenses.index') }}">
        <div class="form-group">
            <label class="form-label" for="search">Pencarian</label>
            <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Deskripsi, kategori, pembuat">
        </div>
        <div class="form-group">
            <label class="form-label" for="date_from">Dari Tanggal</label>
            <input class="form-control" id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="date_to">Sampai Tanggal</label>
            <input class="form-control" id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="branch_id">Cabang</label>
                <select class="form-control" id="branch_id" name="branch_id">
                    <option value="">Semua cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="form-group">
            <label class="form-label" for="expense_category_id">Kategori</label>
            <select class="form-control" id="expense_category_id" name="expense_category_id">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['expense_category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select class="form-control" id="status" name="status">
                <option value="">Semua status</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Menunggu</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Disetujui</option>
                <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Ditolak</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="created_by">Dibuat Oleh</label>
            <select class="form-control" id="created_by" name="created_by">
                <option value="">Semua pengguna</option>
                @foreach ($creators as $creator)
                    <option value="{{ $creator->id }}" @selected((string) ($filters['created_by'] ?? '') === (string) $creator->id)>{{ $creator->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="per_page">Per Halaman</label>
            <select class="form-control" id="per_page" name="per_page">
                @foreach ([15, 25, 50] as $size)
                    <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 15) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="module-filters__actions">
            <a class="btn btn-secondary" href="{{ route('expenses.index') }}">Atur Ulang</a>
            <button class="btn btn-primary" type="submit">Terapkan</button>
        </div>
    </form>
</section>
