@php
    $cashierUser = auth()->user()->loadMissing(['role', 'branch']);
    $cashierInitials = collect(explode(' ', $cashierUser->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
<header class="cashier-header">
    <div class="cashier-header__brand">
        <span class="cashier-header__mark" aria-hidden="true">KS</span>
        <div>
            <strong>{{ config('app.name') }}</strong>
            <span>Ruang Kasir</span>
        </div>
    </div>
    <div class="cashier-header__context">
        <span class="badge badge-warning">Mode Simulasi</span>
        <div>
            <small>Cabang</small>
            <strong data-header-branch>{{ $branch?->name ?? 'Belum dipilih' }}</strong>
        </div>
    </div>
    <div class="cashier-header__user">
        <span class="cashier-header__avatar" aria-hidden="true">{{ $cashierInitials ?: 'AK' }}</span>
        <div>
            <strong>{{ $cashierUser->name }}</strong>
            <span>{{ $cashierUser->role?->name ?? 'Pengguna' }}</span>
        </div>
        <a class="btn btn-sm btn-outline" href="{{ route('account.index') }}">Kembali</a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-secondary" type="submit">Keluar</button>
        </form>
    </div>
</header>
