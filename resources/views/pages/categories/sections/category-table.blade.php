<section class="table-card" aria-label="Daftar kategori">
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>No.</th><th>Kategori</th><th>Deskripsi</th><th>Produk</th><th>Status</th><th>Diperbarui</th><th class="table-actions">Aksi</th></tr></thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $categories->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $category->name }}</strong><small class="table-secondary">{{ $category->slug }}</small></td>
                        <td>{{ \Illuminate\Support\Str::limit($category->description ?: '—', 55) }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td><span class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ $category->updated_at->format('d M Y') }}</td>
                        <td class="table-actions">
                            <div class="action-group">
                                <a class="btn btn-sm btn-outline" href="{{ route('categories.show', $category) }}">Detail</a>
                                @can('update', $category)<a class="btn btn-sm btn-secondary" href="{{ route('categories.edit', $category) }}">Edit</a>@endcan
                                @can('updateStatus', $category)
                                    <button class="btn btn-sm {{ $category->is_active ? 'btn-danger' : 'btn-success' }}" type="button" data-category-status data-action="{{ route('categories.status.update', $category) }}" data-name="{{ $category->name }}" data-next-status="{{ $category->is_active ? '0' : '1' }}">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                @endcan
                                @can('delete', $category)
                                    @if ($category->products_count === 0)
                                        <button class="btn btn-sm btn-danger" type="button" data-category-delete data-action="{{ route('categories.destroy', $category) }}" data-name="{{ $category->name }}">Hapus</button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row"><td colspan="7">Tidak ada kategori yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        <span>Menampilkan {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} dari {{ $categories->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination kategori">
            @if ($categories->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $categories->previousPageUrl() }}">‹</a>@endif
            <span class="pagination-button is-active">{{ $categories->currentPage() }}</span>
            @if ($categories->hasMorePages())<a class="pagination-button" href="{{ $categories->nextPageUrl() }}">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif
        </nav>
    </div>
</section>
