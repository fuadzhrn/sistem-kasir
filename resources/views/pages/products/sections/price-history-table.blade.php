<section class="table-card price-history-card" aria-label="Riwayat harga">
    <div class="table-wrapper price-history__desktop-table">
        <table class="table price-history-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Waktu</th>
                    @if ($isOwner)
                        <th>Harga Beli Lama</th>
                        <th>Harga Beli Baru</th>
                        <th>Perubahan Beli</th>
                    @endif
                    <th>Harga Jual Lama</th>
                    <th>Harga Jual Baru</th>
                    <th>Perubahan Jual</th>
                    <th>Alasan</th>
                    <th>Diubah Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($priceHistories as $history)
                    @php
                        $sellingDifference = \App\Support\Format\Rupiah::difference(
                            $history->old_selling_price,
                            $history->new_selling_price,
                        );
                        $purchaseDifference = $isOwner
                            ? \App\Support\Format\Rupiah::difference(
                                $history->old_purchase_price,
                                $history->new_purchase_price,
                            )
                            : null;
                    @endphp
                    <tr>
                        <td>{{ $priceHistories->firstItem() + $loop->index }}</td>
                        <td>{{ $history->changed_at->format('d M Y H:i') }}</td>
                        @if ($isOwner)
                            <td>{{ \App\Support\Format\Rupiah::format($history->old_purchase_price) }}</td>
                            <td>{{ \App\Support\Format\Rupiah::format($history->new_purchase_price) }}</td>
                            <td>
                                <span class="badge {{ $purchaseDifference > 0 ? 'badge-warning' : ($purchaseDifference < 0 ? 'badge-success' : 'badge-outline') }}">
                                    {{ $purchaseDifference > 0 ? 'Naik ' : ($purchaseDifference < 0 ? 'Turun ' : 'Tetap ') }}{{ \App\Support\Format\Rupiah::formatMinor(abs($purchaseDifference)) }}
                                </span>
                            </td>
                        @endif
                        <td>{{ \App\Support\Format\Rupiah::format($history->old_selling_price) }}</td>
                        <td>{{ \App\Support\Format\Rupiah::format($history->new_selling_price) }}</td>
                        <td>
                            <span class="badge {{ $sellingDifference > 0 ? 'badge-warning' : ($sellingDifference < 0 ? 'badge-success' : 'badge-outline') }}">
                                {{ $sellingDifference > 0 ? 'Naik ' : ($sellingDifference < 0 ? 'Turun ' : 'Tetap ') }}{{ \App\Support\Format\Rupiah::formatMinor(abs($sellingDifference)) }}
                            </span>
                        </td>
                        <td>{{ $history->reason ?: '—' }}</td>
                        <td>{{ $history->changedBy?->name ?: 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="{{ $isOwner ? 10 : 7 }}">Belum ada perubahan harga.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="price-history" aria-label="Timeline riwayat harga">
        @forelse ($priceHistories as $history)
            @php
                $sellingDifference = \App\Support\Format\Rupiah::difference(
                    $history->old_selling_price,
                    $history->new_selling_price,
                );
                $purchaseDifference = $isOwner
                    ? \App\Support\Format\Rupiah::difference(
                        $history->old_purchase_price,
                        $history->new_purchase_price,
                    )
                    : null;
            @endphp
            <article class="price-history__item">
                <header class="price-history__header">
                    <time class="price-history__date">{{ $history->changed_at->format('d M Y · H:i') }}</time>
                    <span class="badge {{ $sellingDifference > 0 ? 'badge-warning' : ($sellingDifference < 0 ? 'badge-success' : 'badge-outline') }}">
                        {{ $sellingDifference > 0 ? 'Naik ' : ($sellingDifference < 0 ? 'Turun ' : 'Tetap ') }}{{ \App\Support\Format\Rupiah::formatMinor(abs($sellingDifference)) }}
                    </span>
                </header>

                <dl class="price-history__prices">
                    <div class="price-history__old-price">
                        <dt>Harga Jual Lama</dt>
                        <dd>{{ \App\Support\Format\Rupiah::format($history->old_selling_price) }}</dd>
                    </div>
                    <div class="price-history__new-price">
                        <dt>Harga Jual Baru</dt>
                        <dd>{{ \App\Support\Format\Rupiah::format($history->new_selling_price) }}</dd>
                    </div>
                </dl>

                @if ($isOwner)
                    <dl class="price-history__purchase">
                        <div>
                            <dt>Harga Beli Lama</dt>
                            <dd>{{ \App\Support\Format\Rupiah::format($history->old_purchase_price) }}</dd>
                        </div>
                        <div>
                            <dt>Harga Beli Baru</dt>
                            <dd>{{ \App\Support\Format\Rupiah::format($history->new_purchase_price) }}</dd>
                        </div>
                        <div>
                            <dt>Perubahan Harga Beli</dt>
                            <dd>
                                {{ $purchaseDifference > 0 ? 'Naik ' : ($purchaseDifference < 0 ? 'Turun ' : 'Tetap ') }}{{ \App\Support\Format\Rupiah::formatMinor(abs($purchaseDifference)) }}
                            </dd>
                        </div>
                    </dl>
                @endif

                <dl class="price-history__meta">
                    <div>
                        <dt>Diubah Oleh</dt>
                        <dd>{{ $history->changedBy?->name ?: 'Sistem' }}</dd>
                    </div>
                    <div>
                        <dt>Catatan</dt>
                        <dd>{{ $history->reason ?: '—' }}</dd>
                    </div>
                </dl>
            </article>
        @empty
            <div class="price-history__empty">
                <h3>Belum ada riwayat perubahan harga</h3>
                <p>Perubahan harga produk akan tampil di sini setelah tersimpan.</p>
            </div>
        @endforelse
    </div>

    <div class="table-pagination price-history__pagination">
        <span>Menampilkan {{ $priceHistories->firstItem() ?? 0 }}–{{ $priceHistories->lastItem() ?? 0 }} dari {{ $priceHistories->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination riwayat harga">
            @if ($priceHistories->onFirstPage())
                <span class="pagination-button" aria-disabled="true">‹</span>
            @else
                <a class="pagination-button" href="{{ $priceHistories->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
            @endif
            <span class="pagination-button is-active" aria-current="page">{{ $priceHistories->currentPage() }}</span>
            @if ($priceHistories->hasMorePages())
                <a class="pagination-button" href="{{ $priceHistories->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="pagination-button" aria-disabled="true">›</span>
            @endif
        </nav>
    </div>
</section>
