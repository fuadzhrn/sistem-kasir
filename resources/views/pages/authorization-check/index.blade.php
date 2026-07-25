@extends('layouts.app')

@section('title', 'Pemeriksaan Otorisasi')
@section('page-title', 'Pemeriksaan Otorisasi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/authorization-check.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Pemeriksaan Otorisasi',
        'description' => 'Ringkasan aman untuk memeriksa role, cabang, Gate, dan cakupan query pada lingkungan lokal.',
        'eyebrow' => 'Local / testing',
    ])

    <section class="authorization-summary">
        <article class="card authorization-identity">
            <span class="authorization-identity__label">Pengguna aktif</span>
            <h3>{{ $user->name }}</h3>
            <dl>
                <div>
                    <dt>Role</dt>
                    <dd>{{ $user->role?->name ?? 'Belum tersedia' }}</dd>
                </div>
                <div>
                    <dt>Cabang</dt>
                    <dd>{{ $user->isOwner() ? 'Semua Cabang' : ($user->branch?->name ?? 'Belum ditetapkan') }}</dd>
                </div>
            </dl>
        </article>

        <article class="card authorization-counts">
            <h3>Cakupan query</h3>
            <div class="authorization-counts__grid">
                <div><strong>{{ $branches->count() }}</strong><span>Cabang terlihat</span></div>
                <div><strong>{{ $visibleSaleCount }}</strong><span>Penjualan contoh</span></div>
                <div><strong>{{ $visibleExpenseCount }}</strong><span>Pengeluaran contoh</span></div>
            </div>
            <p>Jumlah dibatasi melalui query scope sebelum data diambil.</p>
        </article>
    </section>

    <section class="card authorization-section">
        <div class="authorization-section__heading">
            <h3>Matriks kemampuan</h3>
            <p>Hasil evaluasi Gate untuk pengguna yang sedang login.</p>
        </div>
        <div class="authorization-abilities">
            @foreach ($abilities as $ability => $allowed)
                <div>
                    <span>{{ $ability }}</span>
                    <span class="badge {{ $allowed ? 'badge-success' : 'badge-danger' }}">
                        {{ $allowed ? 'Diizinkan' : 'Ditolak' }}
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card authorization-section">
        <div class="authorization-section__heading">
            <h3>Cabang yang dapat diakses</h3>
            <p>Daftar ini berasal dari <code>Branch::accessibleTo()</code>.</p>
        </div>
        @if ($branches->isEmpty())
            <p class="authorization-empty">Tidak ada cabang yang dapat diakses.</p>
        @else
            <div class="authorization-branches">
                @foreach ($branches as $branch)
                    <div>
                        <span><strong>{{ $branch->code }}</strong> — {{ $branch->name }}</span>
                        <a class="btn btn-sm btn-outline" href="{{ route('authorization-check.branch', $branch) }}">Periksa akses</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
