<section class="card sales-filters" data-sales-filters>
    <div class="sales-filters__mobile-heading">
        <div>
            <span>Persempit hasil</span>
            <h2>Filter Transaksi</h2>
        </div>
        <button class="sales-filters__close" type="button" aria-label="Tutup filter transaksi" data-sales-filter-close>×</button>
    </div>

    <form id="sales-filter-panel" action="{{ route('sales.index') }}" method="GET" data-sales-filter-form>
        <div class="sales-filters__grid">
            <div class="form-group sales-filters__search">
                <label class="form-label" for="sale-search">Cari transaksi</label>
                <input
                    class="form-control"
                    id="sale-search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] ?? '' }}"
                    maxlength="100"
                    placeholder="Nomor nota, Kasir, atau metode pembayaran"
                >
            </div>

            @if (auth()->user()->isOwner())
                <div class="form-group">
                    <label class="form-label" for="sale-branch">Cabang</label>
                    <select class="form-select" id="sale-branch" name="branch_id">
                        <option value="">Semua Cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                                {{ $branch->code }} — {{ $branch->name }}{{ $branch->is_active ? '' : ' (Nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif (auth()->user()->isAdmin())
                <div class="form-group">
                    <label class="form-label" for="sale-branch-readonly">Cabang</label>
                    <input class="form-control" id="sale-branch-readonly" value="{{ auth()->user()->branch?->name ?? 'Belum ditetapkan' }}" readonly>
                </div>
            @endif

            @unless (auth()->user()->isCashier())
                <div class="form-group">
                    <label class="form-label" for="sale-cashier">Pengguna pembuat</label>
                    <select class="form-select" id="sale-cashier" name="cashier_id">
                        <option value="">Semua Pengguna</option>
                        @foreach ($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" @selected(($filters['cashier_id'] ?? null) == $cashier->id)>
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endunless

            <div class="form-group">
                <label class="form-label" for="sale-status">Status</label>
                <select class="form-select" id="sale-status" name="status">
                    <option value="">Semua Status</option>
                    <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Selesai</option>
                    <option value="void_requested" @selected(($filters['status'] ?? '') === 'void_requested')>Menunggu Pembatalan</option>
                    <option value="voided" @selected(($filters['status'] ?? '') === 'voided')>Dibatalkan</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="sale-payment-method">Metode pembayaran</label>
                <select class="form-select" id="sale-payment-method" name="payment_method_id">
                    <option value="">Semua Metode</option>
                    @foreach ($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->id }}" @selected(($filters['payment_method_id'] ?? null) == $paymentMethod->id)>
                            {{ $paymentMethod->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="sale-date-from">Tanggal mulai</label>
                <input class="form-control" id="sale-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="sale-date-to">Tanggal akhir</label>
                <input class="form-control" id="sale-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="sale-per-page">Data per halaman</label>
                <select class="form-select" id="sale-per-page" name="per_page">
                    @foreach ([15, 25, 50] as $perPage)
                        <option value="{{ $perPage }}" @selected((int) ($filters['per_page'] ?? 25) === $perPage)>{{ $perPage }} data</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="sales-filters__quick" aria-label="Pilihan tanggal cepat">
            <span>Tanggal cepat:</span>
            <button class="btn btn-ghost btn-sm" type="button" data-date-preset="today">Hari Ini</button>
            <button class="btn btn-ghost btn-sm" type="button" data-date-preset="yesterday">Kemarin</button>
            <button class="btn btn-ghost btn-sm" type="button" data-date-preset="last-7-days">7 Hari Terakhir</button>
            <button class="btn btn-ghost btn-sm" type="button" data-date-preset="this-month">Bulan Ini</button>
        </div>

        <div class="sales-filters__actions">
            <button class="btn btn-secondary" type="button" data-reset-sales-filters>Reset Filter</button>
            <button class="btn btn-primary" type="submit" data-submit-sales-filters>Terapkan Filter</button>
        </div>
    </form>
</section>
