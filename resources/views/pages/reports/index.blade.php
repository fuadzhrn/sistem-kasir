@extends('layouts.app')
@section('title', 'Modul Laporan')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/reports.css') }}">
@endpush
@section('content')
<div class="reports-page">
    <header class="reports-header">
        <div>
            <p class="reports-eyebrow">Analisis operasional dan keuangan</p>
            <h1>Modul Laporan</h1>
            <p>Pilih laporan yang ingin ditinjau. Seluruh laporan bersifat read-only.</p>
        </div>
    </header>
    <section class="reports-landing-grid" aria-label="Daftar laporan">
        @foreach ($reports as $item)
            <article class="report-landing-card card">
                <span class="report-landing-card__icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['name'], 8, 2)) }}</span>
                <h2>{{ $item['name'] }}</h2>
                <p>{{ $item['description'] }}</p>
                <a class="btn btn-primary" href="{{ route($item['route']) }}">Buka Laporan</a>
            </article>
        @endforeach
    </section>
</div>
@endsection
