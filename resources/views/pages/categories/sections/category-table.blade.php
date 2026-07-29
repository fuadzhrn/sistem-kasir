<section class="table-card master-data-table-card" aria-label="Daftar kategori">
    <div class="table-wrapper master-desktop-table">
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

    <div class="master-mobile-list" aria-label="Daftar kategori produk mobile">
        @forelse ($categories as $category)
            @php
                $canUpdateCategory = \Illuminate\Support\Facades\Gate::allows('update', $category);
                $canUpdateCategoryStatus = \Illuminate\Support\Facades\Gate::allows('updateStatus', $category);
                $canDeleteCategory = $category->products_count === 0
                    && \Illuminate\Support\Facades\Gate::allows('delete', $category);
            @endphp
            <article class="master-card">
                <header class="master-card__header">
                    <div class="master-card__identity">
                        <strong class="master-card__title">{{ $category->name }}</strong>
                        <span class="master-card__subtitle">Slug: {{ $category->slug }}</span>
                    </div>
                    <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </header>
                <dl class="master-card__body">
                    <div class="master-card__row">
                        <dt class="master-card__label">Deskripsi</dt>
                        <dd class="master-card__value">{{ $category->description ?: '—' }}</dd>
                    </div>
                    <div class="master-card__row">
                        <dt class="master-card__label">Digunakan</dt>
                        <dd class="master-card__value">{{ $category->products_count }} produk</dd>
                    </div>
                    <div class="master-card__row">
                        <dt class="master-card__label">Diperbarui</dt>
                        <dd class="master-card__value">{{ $category->updated_at->format('d M Y') }}</dd>
                    </div>
                </dl>
                <footer class="master-card__footer">
                    <a class="btn btn-outline" href="{{ route('categories.show', $category) }}">Detail</a>
                    @if ($canUpdateCategory || $canUpdateCategoryStatus || $canDeleteCategory)
                        <details class="master-card__actions" data-master-action-menu>
                            <summary class="btn btn-secondary master-card__action-toggle" aria-expanded="false">
                                Tindakan
                            </summary>
                            <div class="master-card__action-menu" role="menu">
                                @if ($canUpdateCategory)
                                    <a class="btn btn-secondary" href="{{ route('categories.edit', $category) }}" role="menuitem">Edit</a>
                                @endif
                                @if ($canUpdateCategoryStatus)
                                    <button
                                        class="btn {{ $category->is_active ? 'btn-danger' : 'btn-success' }}"
                                        type="button"
                                        role="menuitem"
                                        data-category-status
                                        data-action="{{ route('categories.status.update', $category) }}"
                                        data-name="{{ $category->name }}"
                                        data-next-status="{{ $category->is_active ? '0' : '1' }}"
                                    >
                                        {{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @endif
                                @if ($canDeleteCategory)
                                    <button
                                        class="btn btn-danger"
                                        type="button"
                                        role="menuitem"
                                        data-category-delete
                                        data-action="{{ route('categories.destroy', $category) }}"
                                        data-name="{{ $category->name }}"
                                    >
                                        Hapus
                                    </button>
                                @endif
                            </div>
                        </details>
                    @endif
                </footer>
            </article>
        @empty
            <div class="master-card__empty">
                <h3>Belum ada kategori produk</h3>
                <p>Tidak ada kategori yang sesuai dengan pencarian atau filter saat ini.</p>
                @can('create', \App\Models\Category::class)
                    <a class="btn btn-primary" href="{{ route('categories.create') }}">Tambah Kategori</a>
                @endcan
            </div>
        @endforelse
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} dari {{ $categories->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination kategori">
            @if ($categories->onFirstPage())<span class="pagination-button" aria-disabled="true">‹</span>@else<a class="pagination-button" href="{{ $categories->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>@endif
            <span class="pagination-button is-active" aria-current="page">{{ $categories->currentPage() }}</span>
            @if ($categories->hasMorePages())<a class="pagination-button" href="{{ $categories->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>@else<span class="pagination-button" aria-disabled="true">›</span>@endif
        </nav>
    </div>
</section>
