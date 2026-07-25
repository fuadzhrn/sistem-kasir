@extends('layouts.app')
@section('title', 'Tambah Satuan')
@section('page-title', 'Tambah Satuan')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/units.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Tambah Satuan', 'description' => 'Tambahkan satuan jual produk.', 'eyebrow' => 'Master Data Global'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('units.index') }}">Kembali</a></div>
    <section class="card form-card">
        <div class="alert alert-info">Data ini digunakan bersama oleh seluruh cabang.</div>
        <form method="POST" action="{{ route('units.store') }}">@csrf @include('pages.units.sections.unit-form')</form>
    </section>
@endsection
