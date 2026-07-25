@extends('layouts.app')
@section('title', 'Detail Metode Pembayaran')
@section('page-title', 'Detail Metode Pembayaran')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/payment-methods.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => $paymentMethod->name, 'description' => 'Informasi metode pembayaran global.', 'eyebrow' => 'Detail Metode Pembayaran'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('payment-methods.index') }}">Kembali</a>@can('update', $paymentMethod)<a class="btn btn-primary" href="{{ route('payment-methods.edit', $paymentMethod) }}">Edit</a>@endcan</div>
    <section class="card detail-card"><dl class="detail-list">
        <div><dt>Kode</dt><dd>{{ $paymentMethod->code }}</dd></div><div><dt>Nama</dt><dd>{{ $paymentMethod->name }}</dd></div>
        <div><dt>Jenis</dt><dd>{{ ['cash' => 'Tunai', 'non_cash' => 'Non-Tunai', 'other' => 'Lainnya'][$paymentMethod->type] ?? 'Tidak dikenal' }}</dd></div><div><dt>Urutan Tampil</dt><dd>{{ $paymentMethod->sort_order }}</dd></div>
        <div><dt>Jumlah Transaksi</dt><dd>{{ $paymentMethod->sales_count }}</dd></div><div><dt>Status</dt><dd><span class="badge {{ $paymentMethod->is_active ? 'badge-success' : 'badge-danger' }}">{{ $paymentMethod->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
        <div><dt>Diperbarui</dt><dd>{{ $paymentMethod->updated_at->timezone(config('app.timezone'))->format('d M Y H:i') }}</dd></div>
    </dl><p class="detail-note">Perubahan nama tidak mengubah snapshot metode pembayaran pada transaksi lama.</p></section>
@endsection
