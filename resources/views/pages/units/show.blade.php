@extends('layouts.app')
@section('title', 'Detail Satuan')
@section('page-title', 'Detail Satuan')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/units.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => $unit->name, 'description' => 'Informasi satuan jual global.', 'eyebrow' => 'Detail Satuan'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('units.index') }}">Kembali</a>@can('update', $unit)<a class="btn btn-primary" href="{{ route('units.edit', $unit) }}">Edit</a>@endcan</div>
    <section class="card detail-card"><dl class="detail-list">
        <div><dt>Nama</dt><dd>{{ $unit->name }}</dd></div><div><dt>Simbol</dt><dd>{{ $unit->symbol ?: '—' }}</dd></div>
        <div><dt>Slug</dt><dd>{{ $unit->slug }}</dd></div><div><dt>Jumlah Produk</dt><dd>{{ $unit->products_count }}</dd></div>
        <div><dt>Status</dt><dd><span class="badge {{ $unit->is_active ? 'badge-success' : 'badge-danger' }}">{{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
        <div><dt>Diperbarui</dt><dd>{{ $unit->updated_at->timezone(config('app.timezone'))->format('d M Y H:i') }}</dd></div>
    </dl><p class="detail-note">Satu produk menggunakan satu satuan jual. Konversi antar-satuan tidak diterapkan pada tahap ini.</p></section>
@endsection
