@extends('layouts.app')
@section('title', 'Edit Metode Pembayaran')
@section('page-title', 'Edit Metode Pembayaran')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/payment-methods.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Edit Metode Pembayaran', 'description' => 'Perbarui identitas dan urutan tanpa mengubah status.', 'eyebrow' => 'Khusus Owner'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('payment-methods.show', $paymentMethod) }}">Batal</a></div>
    <section class="card form-card"><div class="alert alert-info">Data ini digunakan bersama oleh seluruh cabang.</div><form method="POST" action="{{ route('payment-methods.update', $paymentMethod) }}">@csrf @method('PUT') @include('pages.payment-methods.sections.payment-method-form', ['paymentMethod' => $paymentMethod])</form></section>
@endsection
