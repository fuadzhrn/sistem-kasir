<section class="table-card" aria-label="Daftar pengguna">
    <div class="table-wrapper">
        <table class="table users-table">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Cabang</th>
                    <th>Status</th>
                    <th>Login terakhir</th>
                    <th class="table-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong><small class="table-secondary">{{ '@'.$user->username }}</small></td>
                        <td>{{ $user->email ?: '—' }}</td>
                        <td><span class="badge badge-outline">{{ $user->role?->name ?? '—' }}</span></td>
                        <td>{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? '—') }}</td>
                        <td><span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum pernah' }}</td>
                        <td class="table-actions">
                            <div class="action-group">
                                <a class="btn btn-sm btn-outline" href="{{ route('users.show', $user) }}">Detail</a>
                                @can('update', $user)
                                    <a class="btn btn-sm btn-secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                                @endcan
                                @can('resetPassword', $user)
                                    <a class="btn btn-sm btn-ghost" href="{{ route('users.password.edit', $user) }}">Reset Password</a>
                                @endcan
                                @if ($viewer->isOwner() && ! $viewer->is($user))
                                    <button
                                        class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}"
                                        type="button"
                                        data-user-status
                                        data-action="{{ route('users.status.update', $user) }}"
                                        data-name="{{ $user->name }}"
                                        data-role="{{ $user->role?->name ?? 'Tanpa role' }}"
                                        data-branch="{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? 'Tanpa cabang') }}"
                                        data-next-status="{{ $user->is_active ? '0' : '1' }}"
                                    >
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @elseif ($viewer->is($user))
                                    <span class="badge badge-info">Akun saat ini</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row"><td colspan="7">Tidak ada pengguna yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        <span>Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }}</span>
        <nav class="pagination-buttons" aria-label="Pagination pengguna">
            @if ($users->onFirstPage())
                <span class="pagination-button" aria-disabled="true">‹</span>
            @else
                <a class="pagination-button" href="{{ $users->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
            @endif
            <span class="pagination-button is-active" aria-current="page">{{ $users->currentPage() }}</span>
            @if ($users->hasMorePages())
                <a class="pagination-button" href="{{ $users->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="pagination-button" aria-disabled="true">›</span>
            @endif
        </nav>
    </div>
</section>
