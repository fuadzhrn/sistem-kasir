<section class="card table-card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Nama</th><th>Deskripsi</th><th>Status</th><th>Digunakan</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong><small class="table-subtext">{{ $category->slug }}</small></td>
                        <td>{{ $category->description ?: '—' }}</td>
                        <td><span class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ $category->expenses_count }} pengeluaran</td>
                        <td class="table-actions">
                            <a class="btn btn-secondary btn-sm" href="{{ route('expense-categories.edit', $category) }}">Edit</a>
                            <button class="btn btn-outline btn-sm" type="button" data-expense-category-status data-action="{{ route('expense-categories.status.update', $category) }}" data-name="{{ $category->name }}" data-next-status="{{ $category->is_active ? 0 : 1 }}">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            @can('delete', $category)
                                <button class="btn btn-danger btn-sm" type="button" data-expense-category-delete data-action="{{ route('expense-categories.destroy', $category) }}" data-name="{{ $category->name }}" @disabled($category->expenses_count > 0)>Hapus</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><h3>Belum ada kategori pengeluaran</h3><p>Tambahkan kategori untuk mulai mencatat pengeluaran.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($categories->hasPages())
        <div class="table-pagination">
            <span>Menampilkan {{ $categories->firstItem() }}–{{ $categories->lastItem() }} dari {{ $categories->total() }}</span>
            <div class="pagination-buttons">
                @if ($categories->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $categories->previousPageUrl() }}">‹</a>@endif
                <span class="pagination-button is-active">{{ $categories->currentPage() }}</span>
                @if ($categories->hasMorePages())<a class="pagination-button" href="{{ $categories->nextPageUrl() }}">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif
            </div>
        </div>
    @endif
</section>
