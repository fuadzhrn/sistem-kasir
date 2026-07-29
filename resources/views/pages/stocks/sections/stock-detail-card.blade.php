@php
    $statusLabels = ['safe' => 'Aman', 'low' => 'Menipis', 'out' => 'Habis'];
    $statusBadges = ['safe' => 'badge-success', 'low' => 'badge-warning', 'out' => 'badge-danger'];
@endphp

<section class="stock-detail-grid">
    <article class="card stock-product-card">
        <img
            src="{{ $branchStock->product->image_path ? asset('storage/'.$branchStock->product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}"
            alt=""
        >
        <div>
            <span class="badge {{ $statusBadges[$stockStatus] }}">{{ $statusLabels[$stockStatus] }}</span>
            <h3>{{ $branchStock->product->name }}</h3>
            <p>{{ $branchStock->product->code }} · {{ $branchStock->product->brand ?: 'Tanpa merek' }}</p>
            <dl class="stock-product-meta">
                <div>
                    <dt>Kategori</dt>
                    <dd>{{ $branchStock->product->category->name }}</dd>
                </div>
                <div>
                    <dt>Satuan</dt>
                    <dd>{{ $branchStock->product->unit->name }}</dd>
                </div>
                <div>
                    <dt>Barcode</dt>
                    <dd>{{ $branchStock->product->barcode ?: 'Tidak tersedia' }}</dd>
                </div>
            </dl>
        </div>
    </article>

    <article class="card stock-detail-card">
        <div class="card__header"><h3>Informasi Stok</h3></div>
        <div class="card__body">
            <div class="stock-detail-sections">
                <section class="stock-detail-section" aria-labelledby="stock-detail-branch">
                    <h4 id="stock-detail-branch">Informasi Cabang</h4>
                    <dl class="stock-detail-list">
                        <div>
                            <dt>Cabang</dt>
                            <dd>{{ $branchStock->branch->code }} — {{ $branchStock->branch->name }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="stock-detail-section" aria-labelledby="stock-detail-summary">
                    <h4 id="stock-detail-summary">Ringkasan Stok</h4>
                    <dl class="stock-detail-list">
                        <div>
                            <dt>Stok Tersedia</dt>
                            <dd class="stock-quantity">
                                {{ \App\Support\Format\Quantity::format($branchStock->quantity) }}
                                {{ $branchStock->product->unit->symbol ?: $branchStock->product->unit->name }}
                            </dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><span class="badge {{ $statusBadges[$stockStatus] }}">{{ $statusLabels[$stockStatus] }}</span></dd>
                        </div>
                        <div>
                            <dt>Diperbarui</dt>
                            <dd>{{ $branchStock->updated_at->format('d M Y, H:i') }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="stock-detail-section" aria-labelledby="stock-detail-minimum">
                    <h4 id="stock-detail-minimum">Stok Minimum</h4>
                    <dl class="stock-detail-list">
                        <div>
                            <dt>Batas Minimum</dt>
                            <dd>
                                {{ \App\Support\Format\Quantity::format($branchStock->product->minimum_stock) }}
                                {{ $branchStock->product->unit->symbol ?: $branchStock->product->unit->name }}
                            </dd>
                        </div>
                    </dl>
                </section>

                @if (auth()->user()->isOwner())
                    <section class="stock-detail-section" aria-labelledby="stock-detail-cost">
                        <h4 id="stock-detail-cost">HPP Rata-rata</h4>
                        <dl class="stock-detail-list">
                            <div>
                                <dt>Biaya Rata-rata Referensi</dt>
                                <dd class="stock-detail-cost">{{ \App\Support\Format\Rupiah::format($branchStock->average_cost) }}</dd>
                            </div>
                        </dl>
                    </section>
                @endif
            </div>
        </div>
    </article>
</section>

@unless ($canCorrect)
    <div class="alert alert-warning" role="status">
        <span class="alert__icon" aria-hidden="true">!</span>
        <div class="alert__content">
            <h4 class="alert__title">Koreksi stok awal tidak tersedia</h4>
            <p class="alert__message">Produk sudah memiliki aktivitas stok operasional. Movement lama tetap utuh dan read-only.</p>
        </div>
    </div>
@endunless
