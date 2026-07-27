<section class="card table-card expense-category-table-card">
    <div class="expense-category-table-wrapper">
        <table class="expense-category-table">
            <colgroup>
                <col class="expense-category-table__column-name">
                <col class="expense-category-table__column-description">
                <col class="expense-category-table__column-status">
                <col class="expense-category-table__column-usage">
                <col class="expense-category-table__column-actions">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Deskripsi</th>
                    <th class="expense-category-table__status" scope="col">Status</th>
                    <th class="expense-category-table__usage" scope="col">Digunakan</th>
                    <th class="expense-category-table__actions-heading" scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="expense-category-table__identity">
                            <strong class="expense-category-table__name">{{ $category->name }}</strong>
                            <span class="expense-category-table__slug">{{ $category->slug }}</span>
                        </td>
                        <td class="expense-category-table__description">
                            <span>{{ $category->description ?: '—' }}</span>
                        </td>
                        <td class="expense-category-table__status">
                            <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="expense-category-table__usage">
                            <span class="expense-category-table__usage-count">{{ $category->expenses_count }}</span>
                            <span class="expense-category-table__usage-label">pengeluaran</span>
                        </td>
                        <td class="expense-category-table__actions-cell">
                            <div class="expense-category-table__actions">
                                <a class="btn btn-secondary btn-sm" href="{{ route('expense-categories.edit', $category) }}">Edit</a>
                                <button class="btn btn-outline btn-sm" type="button" data-expense-category-status data-action="{{ route('expense-categories.status.update', $category) }}" data-name="{{ $category->name }}" data-next-status="{{ $category->is_active ? 0 : 1 }}">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                @can('delete', $category)
                                    <button class="btn btn-danger btn-sm" type="button" data-expense-category-delete data-action="{{ route('expense-categories.destroy', $category) }}" data-name="{{ $category->name }}" @disabled($category->expenses_count > 0)>Hapus</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="expense-category-table__empty-row"><td colspan="5"><div class="empty-state"><h3>Belum ada kategori pengeluaran</h3><p>Tambahkan kategori untuk mulai mencatat pengeluaran.</p></div></td></tr>
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
