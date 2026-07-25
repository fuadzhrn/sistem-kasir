@extends('layouts.app')
@section('title', 'Tambah Metode Pembayaran')
@section('page-title', 'Tambah Metode Pembayaran')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/payment-methods.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Tambah Metode Pembayaran', 'description' => 'Tambahkan pilihan pembayaran baru.', 'eyebrow' => 'Khusus Owner'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('payment-methods.index') }}">Kembali</a></div>
    <section class="card form-card"><div class="alert alert-info">Data ini digunakan bersama oleh seluruh cabang.</div><form method="POST" action="{{ route('payment-methods.store') }}">@csrf @include('pages.payment-methods.sections.payment-method-form')</form></section>
@endsection
