<section class="table-card master-data-table-card" aria-label="Daftar pengguna">
    <div class="table-wrapper master-desktop-table">
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

    <div class="master-mobile-list" aria-label="Daftar pengguna mobile">
        @forelse ($users as $user)
            @php
                $canEditUser = \Illuminate\Support\Facades\Gate::allows('update', $user);
                $canResetUserPassword = \Illuminate\Support\Facades\Gate::allows('resetPassword', $user);
                $canUpdateUserStatus = $viewer->isOwner() && ! $viewer->is($user);
            @endphp
            <article class="master-card">
                <header class="master-card__header">
                    <div class="master-card__identity">
                        <strong class="master-card__title">{{ $user->name }}</strong>
                        <span class="master-card__subtitle">{{ '@'.$user->username }}</span>
                    </div>
                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </header>
                <dl class="master-card__body">
                    <div class="master-card__row">
                        <dt class="master-card__label">Role</dt>
                        <dd class="master-card__value">{{ $user->role?->name ?? '—' }}</dd>
                    </div>
                    <div class="master-card__row">
                        <dt class="master-card__label">Cabang</dt>
                        <dd class="master-card__value">{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? '—') }}</dd>
                    </div>
                    <div class="master-card__row">
                        <dt class="master-card__label">Email</dt>
                        <dd class="master-card__value">{{ $user->email ?: '—' }}</dd>
                    </div>
                    <div class="master-card__row">
                        <dt class="master-card__label">Login Terakhir</dt>
                        <dd class="master-card__value">{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Belum pernah' }}</dd>
                    </div>
                </dl>
                <footer class="master-card__footer">
                    <a class="btn btn-outline" href="{{ route('users.show', $user) }}">Detail</a>
                    @if ($canEditUser || $canResetUserPassword || $canUpdateUserStatus)
                        <details class="master-card__actions" data-master-action-menu>
                            <summary class="btn btn-secondary master-card__action-toggle" aria-expanded="false">
                                Tindakan
                            </summary>
                            <div class="master-card__action-menu" role="menu">
                                @if ($canEditUser)
                                    <a class="btn btn-secondary" href="{{ route('users.edit', $user) }}" role="menuitem">Edit</a>
                                @endif
                                @if ($canResetUserPassword)
                                    <a class="btn btn-outline" href="{{ route('users.password.edit', $user) }}" role="menuitem">Reset Password</a>
                                @endif
                                @if ($canUpdateUserStatus)
                                    <button
                                        class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }}"
                                        type="button"
                                        role="menuitem"
                                        data-user-status
                                        data-action="{{ route('users.status.update', $user) }}"
                                        data-name="{{ $user->name }}"
                                        data-role="{{ $user->role?->name ?? 'Tanpa role' }}"
                                        data-branch="{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? 'Tanpa cabang') }}"
                                        data-next-status="{{ $user->is_active ? '0' : '1' }}"
                                    >
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @endif
                            </div>
                        </details>
                    @else
                        <span class="badge badge-info">Read-only</span>
                    @endif
                </footer>
            </article>
        @empty
            <div class="master-card__empty">
                <h3>Belum ada pengguna</h3>
                <p>Tidak ada pengguna yang sesuai dengan pencarian atau filter saat ini.</p>
                @can('create', \App\Models\User::class)
                    <a class="btn btn-primary" href="{{ route('users.create') }}">Tambah Pengguna</a>
                @endcan
            </div>
        @endforelse
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
