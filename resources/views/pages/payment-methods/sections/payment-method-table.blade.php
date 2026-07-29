<section class="table-card master-data-table-card" aria-label="Daftar metode pembayaran"><div class="table-wrapper master-desktop-table"><table class="table">
    <thead><tr><th>No.</th><th>Kode</th><th>Nama</th><th>Jenis</th><th>Urutan</th><th>Transaksi</th><th>Status</th><th class="table-actions">Aksi</th></tr></thead>
    <tbody>@forelse($paymentMethods as $paymentMethod)<tr>
        <td>{{ $paymentMethods->firstItem() + $loop->index }}</td><td><strong>{{ $paymentMethod->code }}</strong></td><td>{{ $paymentMethod->name }}</td><td><span class="badge badge-info">{{ ['cash' => 'Tunai', 'non_cash' => 'Non-Tunai', 'other' => 'Lainnya'][$paymentMethod->type] ?? 'Tidak dikenal' }}</span></td><td>{{ $paymentMethod->sort_order }}</td><td>{{ $paymentMethod->sales_count }}</td><td><span class="badge {{ $paymentMethod->is_active ? 'badge-success' : 'badge-danger' }}">{{ $paymentMethod->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
        <td class="table-actions"><div class="action-group"><a class="btn btn-sm btn-outline" href="{{ route('payment-methods.show', $paymentMethod) }}">Detail</a>@can('update', $paymentMethod)<a class="btn btn-sm btn-secondary" href="{{ route('payment-methods.edit', $paymentMethod) }}">Edit</a>@endcan @can('updateStatus', $paymentMethod)<button class="btn btn-sm {{ $paymentMethod->is_active ? 'btn-danger' : 'btn-success' }}" type="button" data-payment-status data-action="{{ route('payment-methods.status.update', $paymentMethod) }}" data-name="{{ $paymentMethod->name }}" data-next-status="{{ $paymentMethod->is_active ? '0' : '1' }}">{{ $paymentMethod->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>@endcan @can('delete', $paymentMethod)@if($paymentMethod->sales_count === 0)<button class="btn btn-sm btn-danger" type="button" data-payment-delete data-action="{{ route('payment-methods.destroy', $paymentMethod) }}" data-name="{{ $paymentMethod->name }}">Hapus</button>@endif @endcan</div></td>
    </tr>@empty<tr class="table-empty-row"><td colspan="8">Tidak ada metode pembayaran yang sesuai dengan filter.</td></tr>@endforelse</tbody>
</table></div>
<div class="master-mobile-list" aria-label="Daftar metode pembayaran mobile">
    @forelse ($paymentMethods as $paymentMethod)
        @php
            $canUpdatePaymentMethod = \Illuminate\Support\Facades\Gate::allows('update', $paymentMethod);
            $canUpdatePaymentMethodStatus = \Illuminate\Support\Facades\Gate::allows('updateStatus', $paymentMethod);
            $canDeletePaymentMethod = $paymentMethod->sales_count === 0
                && \Illuminate\Support\Facades\Gate::allows('delete', $paymentMethod);
            $paymentMethodType = ['cash' => 'Tunai', 'non_cash' => 'Non-Tunai', 'other' => 'Lainnya'][$paymentMethod->type]
                ?? 'Tidak dikenal';
        @endphp
        <article class="master-card">
            <header class="master-card__header">
                <div class="master-card__identity">
                    <strong class="master-card__title">{{ $paymentMethod->name }}</strong>
                    <span class="master-card__subtitle">Kode: {{ $paymentMethod->code }}</span>
                </div>
                <span class="badge {{ $paymentMethod->is_active ? 'badge-success' : 'badge-danger' }}">{{ $paymentMethod->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </header>
            <dl class="master-card__body">
                <div class="master-card__row">
                    <dt class="master-card__label">Jenis</dt>
                    <dd class="master-card__value">{{ $paymentMethodType }}</dd>
                </div>
                <div class="master-card__row">
                    <dt class="master-card__label">Urutan Tampil</dt>
                    <dd class="master-card__value">{{ $paymentMethod->sort_order }}</dd>
                </div>
                <div class="master-card__row">
                    <dt class="master-card__label">Digunakan</dt>
                    <dd class="master-card__value">{{ $paymentMethod->sales_count }} transaksi</dd>
                </div>
            </dl>
            <footer class="master-card__footer">
                <a class="btn btn-outline" href="{{ route('payment-methods.show', $paymentMethod) }}">Detail</a>
                @if ($canUpdatePaymentMethod || $canUpdatePaymentMethodStatus || $canDeletePaymentMethod)
                    <details class="master-card__actions" data-master-action-menu>
                        <summary class="btn btn-secondary master-card__action-toggle" aria-expanded="false">Tindakan</summary>
                        <div class="master-card__action-menu" role="menu">
                            @if ($canUpdatePaymentMethod)
                                <a class="btn btn-secondary" href="{{ route('payment-methods.edit', $paymentMethod) }}" role="menuitem">Edit</a>
                            @endif
                            @if ($canUpdatePaymentMethodStatus)
                                <button class="btn {{ $paymentMethod->is_active ? 'btn-danger' : 'btn-success' }}" type="button" role="menuitem" data-payment-status data-action="{{ route('payment-methods.status.update', $paymentMethod) }}" data-name="{{ $paymentMethod->name }}" data-next-status="{{ $paymentMethod->is_active ? '0' : '1' }}">{{ $paymentMethod->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            @endif
                            @if ($canDeletePaymentMethod)
                                <button class="btn btn-danger" type="button" role="menuitem" data-payment-delete data-action="{{ route('payment-methods.destroy', $paymentMethod) }}" data-name="{{ $paymentMethod->name }}">Hapus</button>
                            @endif
                        </div>
                    </details>
                @endif
            </footer>
        </article>
    @empty
        <div class="master-card__empty">
            <h3>Belum ada metode pembayaran</h3>
            <p>Tidak ada metode pembayaran yang sesuai dengan pencarian atau filter saat ini.</p>
            @can('create', \App\Models\PaymentMethod::class)
                <a class="btn btn-primary" href="{{ route('payment-methods.create') }}">Tambah Metode Pembayaran</a>
            @endcan
        </div>
    @endforelse
</div>
<div class="table-pagination"><span>Menampilkan {{ $paymentMethods->firstItem() ?? 0 }}–{{ $paymentMethods->lastItem() ?? 0 }} dari {{ $paymentMethods->total() }}</span><nav class="pagination-buttons" aria-label="Pagination metode pembayaran">@if($paymentMethods->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $paymentMethods->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif<span class="pagination-button is-active" aria-current="page">{{ $paymentMethods->currentPage() }}</span>@if($paymentMethods->hasMorePages())<a class="pagination-button" href="{{ $paymentMethods->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif</nav></div></section>
