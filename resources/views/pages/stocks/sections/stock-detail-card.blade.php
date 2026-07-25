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
        </div>
    </article>

    <article class="card stock-detail-card">
        <div class="card__header"><h3>Informasi Stok</h3></div>
        <div class="card__body">
            <dl class="stock-detail-list">
                <div><dt>Cabang</dt><dd>{{ $branchStock->branch->code }} — {{ $branchStock->branch->name }}</dd></div>
                <div><dt>Quantity</dt><dd class="stock-quantity">{{ \App\Support\Format\Quantity::format($branchStock->quantity) }} {{ $branchStock->product->unit->symbol }}</dd></div>
                <div><dt>Stok Minimum</dt><dd>{{ \App\Support\Format\Quantity::format($branchStock->product->minimum_stock) }} {{ $branchStock->product->unit->symbol }}</dd></div>
                <div><dt>Status</dt><dd>{{ $statusLabels[$stockStatus] }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $branchStock->product->category->name }}</dd></div>
                <div><dt>Satuan</dt><dd>{{ $branchStock->product->unit->name }}</dd></div>
                <div><dt>Barcode</dt><dd>{{ $branchStock->product->barcode ?: 'Tidak tersedia' }}</dd></div>
                <div><dt>Diperbarui</dt><dd>{{ $branchStock->updated_at->format('d M Y, H:i') }}</dd></div>
                @if (auth()->user()->isOwner())
                    <div><dt>Biaya Rata-rata Referensi</dt><dd>{{ \App\Support\Format\Rupiah::format($branchStock->average_cost) }}</dd></div>
                @endif
            </dl>
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
