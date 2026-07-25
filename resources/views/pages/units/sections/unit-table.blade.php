<section class="table-card" aria-label="Daftar satuan"><div class="table-wrapper"><table class="table">
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
</table></div><div class="table-pagination"><span>Menampilkan {{ $units->firstItem() ?? 0 }}–{{ $units->lastItem() ?? 0 }} dari {{ $units->total() }}</span><nav class="pagination-buttons" aria-label="Pagination satuan">@if($units->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $units->previousPageUrl() }}">‹</a>@endif<span class="pagination-button is-active">{{ $units->currentPage() }}</span>@if($units->hasMorePages())<a class="pagination-button" href="{{ $units->nextPageUrl() }}">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif</nav></div></section>
