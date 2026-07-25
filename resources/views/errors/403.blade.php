@extends('layouts.app')

@section('title', 'Akses Ditolak')
@section('page-title', 'Akses Ditolak')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/error-403.css') }}">
@endpush

@section('content')
    <section class="error-access card" role="alert">
        <span class="error-access__code" aria-hidden="true">403</span>
        <p class="error-access__eyebrow">Permintaan tidak diizinkan</p>
        <h2>Akses Ditolak</h2>
        <p>Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <div class="error-access__actions">
            <button class="btn btn-secondary" type="button" data-history-back>Kembali</button>
            <a class="btn btn-primary" href="{{ route('account.index') }}">Ke Akun Saya</a>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/error-403.js') }}" defer></script>
@endpush
