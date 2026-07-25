@extends('layouts.app')

@section('title', 'Cabang Saya')
@section('page-title', 'Cabang Saya')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/branches.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $branch->name,
        'description' => 'Informasi cabang akun Anda. Halaman ini bersifat read-only.',
        'eyebrow' => 'Cabang Saya',
    ])

    <section class="detail-grid">
        <article class="card detail-card">
            <h3>Identitas cabang</h3>
            <dl class="detail-list">
                <div><dt>Kode</dt><dd>{{ $branch->code }}</dd></div>
                <div><dt>Nama</dt><dd>{{ $branch->name }}</dd></div>
                <div><dt>Telepon</dt><dd>{{ $branch->phone ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Status</dt><dd><span class="badge {{ $branch->is_active ? 'badge-success' : 'badge-danger' }}">{{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
                <div class="detail-list__full"><dt>Alamat</dt><dd>{{ $branch->address ?: 'Belum tersedia' }}</dd></div>
            </dl>
        </article>

        <article class="card detail-card">
            <h3>Ringkasan cabang</h3>
            <dl class="metric-list">
                <div><dt>Pengguna</dt><dd>{{ $branch->users_count }}</dd></div>
                <div><dt>Baris stok</dt><dd>{{ $branch->branch_stocks_count }}</dd></div>
                <div><dt>Penerimaan stok</dt><dd>{{ $branch->stock_receipts_count }}</dd></div>
                <div><dt>Transaksi</dt><dd>{{ $branch->sales_count }}</dd></div>
            </dl>
        </article>
    </section>
@endsection
