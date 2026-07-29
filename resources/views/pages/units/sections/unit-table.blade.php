<section class="table-card master-data-table-card" aria-label="Daftar satuan"><div class="table-wrapper master-desktop-table"><table class="table">
    <thead><tr><th>No.</th><th>Nama</th><th>Simbol</th><th>Slug</th><th>Produk</th><th>Status</th><th>Diperbarui</th><th class="table-actions">Aksi</th></tr></thead>
    <tbody>@forelse($units as $unit)<tr>
        <td>{{ $units->firstItem() + $loop->index }}</td><td><strong>{{ $unit->name }}</strong></td><td>{{ $unit->symbol ?: '—' }}</td><td>{{ $unit->slug }}</td><td>{{ $unit->products_count }}</td>
        <td><span class="badge {{ $unit->is_active ? 'badge-success' : 'badge-danger' }}">{{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $unit->updated_at->format('d M Y') }}</td>
        <td class="table-actions"><div class="action-group"><a class="btn btn-sm btn-outline" href="{{ route('units.show', $unit) }}">Detail</a>
            @can('update', $unit)<a class="btn btn-sm btn-secondary" href="{{ route('units.edit', $unit) }}">Edit</a>@endcan
            @can('updateStatus', $unit)<button class="btn btn-sm {{ $unit->is_active ? 'btn-danger' : 'btn-success' }}" type="button" data-unit-status data-action="{{ route('units.status.update', $unit) }}" data-name="{{ $unit->name }}" data-next-status="{{ $unit->is_active ? '0' : '1' }}">{{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>@endcan
            @can('delete', $unit)@if($unit->products_count === 0)<button class="btn btn-sm btn-danger" type="button" data-unit-delete data-action="{{ route('units.destroy', $unit) }}" data-name="{{ $unit->name }}">Hapus</button>@endif @endcan
        </div></td>
    </tr>@empty<tr class="table-empty-row"><td colspan="8">Tidak ada satuan yang sesuai dengan filter.</td></tr>@endforelse</tbody>
</table></div>
<div class="master-mobile-list" aria-label="Daftar satuan mobile">
    @forelse ($units as $unit)
        @php
            $canUpdateUnit = \Illuminate\Support\Facades\Gate::allows('update', $unit);
            $canUpdateUnitStatus = \Illuminate\Support\Facades\Gate::allows('updateStatus', $unit);
            $canDeleteUnit = $unit->products_count === 0
                && \Illuminate\Support\Facades\Gate::allows('delete', $unit);
        @endphp
        <article class="master-card">
            <header class="master-card__header">
                <div class="master-card__identity">
                    <strong class="master-card__title">{{ $unit->name }}</strong>
                    <span class="master-card__subtitle">Slug: {{ $unit->slug }}</span>
                </div>
                <span class="badge {{ $unit->is_active ? 'badge-success' : 'badge-danger' }}">{{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </header>
            <dl class="master-card__body">
                <div class="master-card__row">
                    <dt class="master-card__label">Simbol</dt>
                    <dd class="master-card__value">{{ $unit->symbol ?: '—' }}</dd>
                </div>
                <div class="master-card__row">
                    <dt class="master-card__label">Digunakan</dt>
                    <dd class="master-card__value">{{ $unit->products_count }} produk</dd>
                </div>
                <div class="master-card__row">
                    <dt class="master-card__label">Diperbarui</dt>
                    <dd class="master-card__value">{{ $unit->updated_at->format('d M Y') }}</dd>
                </div>
            </dl>
            <footer class="master-card__footer">
                <a class="btn btn-outline" href="{{ route('units.show', $unit) }}">Detail</a>
                @if ($canUpdateUnit || $canUpdateUnitStatus || $canDeleteUnit)
                    <details class="master-card__actions" data-master-action-menu>
                        <summary class="btn btn-secondary master-card__action-toggle" aria-expanded="false">Tindakan</summary>
                        <div class="master-card__action-menu" role="menu">
                            @if ($canUpdateUnit)
                                <a class="btn btn-secondary" href="{{ route('units.edit', $unit) }}" role="menuitem">Edit</a>
                            @endif
                            @if ($canUpdateUnitStatus)
                                <button class="btn {{ $unit->is_active ? 'btn-danger' : 'btn-success' }}" type="button" role="menuitem" data-unit-status data-action="{{ route('units.status.update', $unit) }}" data-name="{{ $unit->name }}" data-next-status="{{ $unit->is_active ? '0' : '1' }}">{{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            @endif
                            @if ($canDeleteUnit)
                                <button class="btn btn-danger" type="button" role="menuitem" data-unit-delete data-action="{{ route('units.destroy', $unit) }}" data-name="{{ $unit->name }}">Hapus</button>
                            @endif
                        </div>
                    </details>
                @endif
            </footer>
        </article>
    @empty
        <div class="master-card__empty">
            <h3>Belum ada satuan</h3>
            <p>Tidak ada satuan yang sesuai dengan pencarian atau filter saat ini.</p>
            @can('create', \App\Models\Unit::class)
                <a class="btn btn-primary" href="{{ route('units.create') }}">Tambah Satuan</a>
            @endcan
        </div>
    @endforelse
</div>
<div class="table-pagination"><span>Menampilkan {{ $units->firstItem() ?? 0 }}–{{ $units->lastItem() ?? 0 }} dari {{ $units->total() }}</span><nav class="pagination-buttons" aria-label="Pagination satuan">@if($units->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $units->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif<span class="pagination-button is-active" aria-current="page">{{ $units->currentPage() }}</span>@if($units->hasMorePages())<a class="pagination-button" href="{{ $units->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif</nav></div></section>
