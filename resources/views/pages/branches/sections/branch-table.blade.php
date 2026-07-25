<section class="table-card" aria-label="Daftar cabang">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Cabang</th>
                    <th>Telepon</th>
                    <th>Pengguna</th>
                    <th>Status</th>
                    <th class="table-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td><strong>{{ $branch->code }}</strong></td>
                        <td>
                            <strong>{{ $branch->name }}</strong>
                            <small class="table-secondary">{{ \Illuminate\Support\Str::limit($branch->address ?: 'Alamat belum tersedia', 48) }}</small>
                        </td>
                        <td>{{ $branch->phone ?: '—' }}</td>
                        <td>{{ $branch->users_count }}</td>
                        <td><span class="badge {{ $branch->is_active ? 'badge-success' : 'badge-danger' }}">{{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="table-actions">
                            <div class="action-group">
                                <a class="btn btn-sm btn-outline" href="{{ route('branches.show', $branch) }}">Detail</a>
                                <a class="btn btn-sm btn-secondary" href="{{ route('branches.edit', $branch) }}">Edit</a>
                                @can('updateStatus', $branch)
                                    <button
                                        class="btn btn-sm {{ $branch->is_active ? 'btn-danger' : 'btn-success' }}"
                                        type="button"
                                        data-branch-status
                                        data-action="{{ route('branches.status.update', $branch) }}"
                                        data-name="{{ $branch->name }}"
                                        data-next-status="{{ $branch->is_active ? '0' : '1' }}"
                                    >
                                        {{ $branch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row"><td colspan="6">Tidak ada cabang yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-pagination">
        <span>Menampilkan {{ $branches->firstItem() ?? 0 }}–{{ $branches->lastItem() ?? 0 }} dari {{ $branches->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination cabang">
            @if ($branches->onFirstPage())
                <span class="pagination-button" aria-disabled="true">‹</span>
            @else
                <a class="pagination-button" href="{{ $branches->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
            @endif
            <span class="pagination-button is-active" aria-current="page">{{ $branches->currentPage() }}</span>
            @if ($branches->hasMorePages())
                <a class="pagination-button" href="{{ $branches->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="pagination-button" aria-disabled="true">›</span>
            @endif
        </nav>
    </div>
</section>
