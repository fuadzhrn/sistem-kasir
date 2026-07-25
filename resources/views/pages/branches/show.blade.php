@extends('layouts.app')

@section('title', 'Detail Cabang')
@section('page-title', 'Detail Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/branches.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => $branch->name,
        'description' => 'Informasi identitas dan ringkasan relasi cabang.',
        'eyebrow' => $branch->code,
        'breadcrumbs' => [
            ['label' => 'Cabang', 'url' => route('branches.index')],
            ['label' => $branch->name],
        ],
    ])

    <div class="module-actions">
        <a class="btn btn-secondary" href="{{ route('branches.index') }}">Kembali</a>
        @can('update', $branch)
            <a class="btn btn-primary" href="{{ route('branches.edit', $branch) }}">Edit Cabang</a>
        @endcan
    </div>

    <section class="detail-grid">
        <article class="card detail-card">
            <h3>Identitas cabang</h3>
            <dl class="detail-list">
                <div><dt>Kode</dt><dd>{{ $branch->code }}</dd></div>
                <div><dt>Nama</dt><dd>{{ $branch->name }}</dd></div>
                <div><dt>Telepon</dt><dd>{{ $branch->phone ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Status</dt><dd><span class="badge {{ $branch->is_active ? 'badge-success' : 'badge-danger' }}">{{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
                <div class="detail-list__full"><dt>Alamat</dt><dd>{{ $branch->address ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Dibuat</dt><dd>{{ $branch->created_at->format('d M Y, H:i') }}</dd></div>
                <div><dt>Diperbarui</dt><dd>{{ $branch->updated_at->format('d M Y, H:i') }}</dd></div>
            </dl>
        </article>

        <article class="card detail-card">
            <h3>Ringkasan relasi</h3>
            <dl class="metric-list">
                <div><dt>Pengguna</dt><dd>{{ $branch->users_count }}</dd></div>
                <div><dt>Baris stok</dt><dd>{{ $branch->branch_stocks_count }}</dd></div>
                <div><dt>Penerimaan stok</dt><dd>{{ $branch->stock_receipts_count }}</dd></div>
                <div><dt>Transaksi</dt><dd>{{ $branch->sales_count }}</dd></div>
            </dl>
            <p class="detail-note">Ringkasan hanya menampilkan jumlah relasi yang sudah tersedia, bukan laporan bisnis.</p>
        </article>
    </section>
@endsection
