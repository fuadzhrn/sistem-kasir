@php
    $statusLabels = ['safe' => 'Aman', 'low' => 'Menipis', 'out' => 'Habis'];
    $statusBadges = ['safe' => 'badge-success', 'low' => 'badge-warning', 'out' => 'badge-danger'];
@endphp

<section class="card stock-selection-card">
    <div class="card__header">
        <h3>Pilih Data Stok</h3>
    </div>
    <div class="card__body">
        <form class="stock-selection-form" method="GET" action="{{ route('stocks.initial.create') }}">
            @if (auth()->user()->isOwner())
                <div class="form-group">
                    <label class="form-label" for="initial-branch">Cabang <span class="form-required">*</span></label>
                    <select class="form-select" id="initial-branch" name="branch_id" required>
                        <option value="">Pilih cabang aktif</option>
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected($branch?->is($branchOption))>
                                {{ $branchOption->code }} — {{ $branchOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="form-group">
                    <span class="form-label">Cabang</span>
                    <div class="stock-readonly-value">{{ $branch->code }} — {{ $branch->name }}</div>
                </div>
            @endif
            <div class="form-group">
                <label class="form-label" for="initial-product">Produk <span class="form-required">*</span></label>
                <select class="form-select" id="initial-product" name="product_id" required>
                    <option value="">Pilih produk aktif</option>
                    @foreach ($products as $productOption)
                        <option value="{{ $productOption->id }}" @selected($product?->is($productOption))>
                            {{ $productOption->code }} — {{ $productOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary" type="submit">Tampilkan Data</button>
        </form>
    </div>
</section>

@if ($branch && $product)
    <section class="card initial-stock-card">
        <div class="card__header">
            <div>
                <h3>{{ $product->name }}</h3>
                <p>{{ $product->code }} · {{ $branch->name }}</p>
            </div>
            <span class="badge {{ $statusBadges[$stockStatus] }}">{{ $statusLabels[$stockStatus] }}</span>
        </div>
        <div class="card__body">
            <dl class="initial-stock-metrics">
                <div><dt>Cabang</dt><dd>{{ $branch->code }} — {{ $branch->name }}</dd></div>
                <div><dt>Produk</dt><dd>{{ $product->code }} — {{ $product->name }}</dd></div>
                <div><dt>Satuan</dt><dd>{{ $product->unit->symbol ?: $product->unit->name }}</dd></div>
                <div><dt>Quantity Saat Ini</dt><dd>{{ \App\Support\Format\Quantity::format($branchStock?->quantity) }}</dd></div>
                <div><dt>Stok Minimum</dt><dd>{{ \App\Support\Format\Quantity::format($product->minimum_stock) }}</dd></div>
                <div><dt>Status Saat Ini</dt><dd>{{ $statusLabels[$stockStatus] }}</dd></div>
            </dl>

            <div class="alert alert-warning" role="status">
                <span class="alert__icon" aria-hidden="true">!</span>
                <div class="alert__content">
                    <h4 class="alert__title">Perubahan akan dicatat</h4>
                    <p class="alert__message">Stok tidak dapat diubah tanpa alasan. Setiap perubahan akan dicatat dalam riwayat stok.</p>
                </div>
            </div>

            @if (! $canCorrect)
                <div class="alert alert-danger" role="alert">
                    <span class="alert__icon" aria-hidden="true">×</span>
                    <div class="alert__content">
                        <h4 class="alert__title">Koreksi stok awal dikunci</h4>
                        <p class="alert__message">Produk sudah memiliki aktivitas stok. Penyesuaian akan tersedia pada tahap berikutnya.</p>
                    </div>
                </div>
            @elseif (! $hasReferenceCost)
                <div class="alert alert-warning" role="alert">
                    <span class="alert__icon" aria-hidden="true">!</span>
                    <div class="alert__content">
                        <h4 class="alert__title">Harga modal referensi belum tersedia</h4>
                        <p class="alert__message">
                            Tetapkan harga beli produk sebelum menyimpan stok positif.
                            <a href="{{ route('products.edit', $product) }}">Buka halaman edit produk</a>.
                        </p>
                    </div>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('stocks.initial.store') }}"
                data-initial-stock-form
                data-quantity-before="{{ $branchStock?->quantity ?? '0.000' }}"
                data-unit="{{ $product->unit->symbol ?: $product->unit->name }}"
                data-product-name="{{ $product->name }}"
                data-branch-name="{{ $branch->name }}"
            >
                @csrf
                @if (auth()->user()->isOwner())
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                @endif
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="initial-quantity">
                            Jumlah Akhir Stok Awal <span class="form-required">*</span>
                        </label>
                        <input
                            class="form-control @error('quantity') is-error @enderror"
                            id="initial-quantity"
                            name="quantity"
                            type="text"
                            inputmode="decimal"
                            value="{{ \App\Support\Format\Quantity::inputValue(old('quantity', $branchStock?->quantity ?? '0')) }}"
                            data-quantity-input
                            required
                            @disabled(! $canCorrect)
                        >
                        @error('quantity')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group form-group--full">
                        <label class="form-label" for="initial-reason">Alasan <span class="form-required">*</span></label>
                        <textarea
                            class="form-textarea @error('reason') is-error @enderror"
                            id="initial-reason"
                            name="reason"
                            minlength="5"
                            maxlength="500"
                            required
                            @disabled(! $canCorrect)
                            placeholder="Contoh: Stok awal hasil perhitungan pembukaan toko"
                        >{{ old('reason') }}</textarea>
                        @error('reason')<span class="form-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn btn-secondary" href="{{ route('stocks.index', auth()->user()->isOwner() ? ['branch_id' => $branch->id] : []) }}">Batal</a>
                    <button class="btn btn-primary" type="submit" data-initial-stock-submit @disabled(! $canCorrect)>
                        Tinjau dan Simpan
                    </button>
                </div>
            </form>
        </div>
    </section>
@endif
