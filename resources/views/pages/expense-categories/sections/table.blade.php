<section class="card table-card expense-category-table-card master-data-table-card">
    <div class="expense-category-table-wrapper master-desktop-table">
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
    <div class="master-mobile-list" aria-label="Daftar kategori pengeluaran mobile">
        @forelse ($categories as $category)
            @php
                $canDeleteExpenseCategory = $category->expenses_count === 0
                    && \Illuminate\Support\Facades\Gate::allows('delete', $category);
            @endphp
            <article class="master-card">
                <header class="master-card__header">
                    <div class="master-card__identity">
                        <strong class="master-card__title">{{ $category->name }}</strong>
                        <span class="master-card__subtitle">Slug: {{ $category->slug }}</span>
                    </div>
                    <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </header>
                <dl class="master-card__body">
                    <div class="master-card__row">
                        <dt class="master-card__label">Deskripsi</dt>
                        <dd class="master-card__value">{{ $category->description ?: '—' }}</dd>
                    </div>
                    <div class="master-card__row">
                        <dt class="master-card__label">Digunakan</dt>
                        <dd class="master-card__value">{{ $category->expenses_count }} pengeluaran</dd>
                    </div>
                </dl>
                <footer class="master-card__footer">
                    <a class="btn btn-outline" href="{{ route('expense-categories.edit', $category) }}">Edit</a>
                    <details class="master-card__actions" data-master-action-menu>
                        <summary class="btn btn-secondary master-card__action-toggle" aria-expanded="false">Tindakan</summary>
                        <div class="master-card__action-menu" role="menu">
                            <button class="btn {{ $category->is_active ? 'btn-danger' : 'btn-success' }}" type="button" role="menuitem" data-expense-category-status data-action="{{ route('expense-categories.status.update', $category) }}" data-name="{{ $category->name }}" data-next-status="{{ $category->is_active ? 0 : 1 }}">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            @if ($canDeleteExpenseCategory)
                                <button class="btn btn-danger" type="button" role="menuitem" data-expense-category-delete data-action="{{ route('expense-categories.destroy', $category) }}" data-name="{{ $category->name }}">Hapus</button>
                            @endif
                        </div>
                    </details>
                </footer>
            </article>
        @empty
            <div class="master-card__empty">
                <h3>Belum ada kategori pengeluaran</h3>
                <p>Tidak ada kategori pengeluaran yang sesuai dengan pencarian atau filter saat ini.</p>
                <a class="btn btn-primary" href="{{ route('expense-categories.create') }}">Tambah Kategori Pengeluaran</a>
            </div>
        @endforelse
    </div>
    @if ($categories->hasPages())
        <div class="table-pagination">
            <span>Menampilkan {{ $categories->firstItem() }}–{{ $categories->lastItem() }} dari {{ $categories->total() }}</span>
            <nav class="pagination-buttons" aria-label="Pagination kategori pengeluaran">
                @if ($categories->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $categories->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif
                <span class="pagination-button is-active" aria-current="page">{{ $categories->currentPage() }}</span>
                @if ($categories->hasMorePages())<a class="pagination-button" href="{{ $categories->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif
            </nav>
        </div>
    @endif
</section>
