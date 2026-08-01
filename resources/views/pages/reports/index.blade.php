@extends('layouts.app')
@section('title', 'Modul Laporan')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/reports.css') }}">
@endpush
@section('content')
@php
    $reportsBySlug = collect($reports)->keyBy('slug');
    $reportGroups = [
        [
            'title' => 'Penjualan dan Keuangan',
            'description' => 'Transaksi, nota, biaya, dan profitabilitas.',
            'slugs' => ['sales', 'receipts', 'cost', 'gross', 'net', 'expenses'],
        ],
        [
            'title' => 'Stok dan Produk',
            'description' => 'Persediaan, penerimaan, pergerakan, dan histori produk.',
            'slugs' => ['stocks', 'receiving', 'movements', 'top', 'prices'],
        ],
        [
            'title' => 'Operasional',
            'description' => 'Kinerja cabang, pengguna kasir, dan pembatalan transaksi.',
            'slugs' => ['branches', 'cashiers', 'voids'],
        ],
    ];
@endphp
<div class="reports-page">
    <header class="reports-header">
        <div class="reports-header__content">
            <p class="reports-eyebrow">Analisis operasional dan keuangan</p>
            <h1>Modul Laporan</h1>
            <p>Pilih laporan yang ingin ditinjau. Seluruh laporan bersifat read-only.</p>
        </div>
    </header>
    @foreach ($reportGroups as $group)
        <section class="reports-landing-group" aria-labelledby="report-group-{{ $loop->index }}">
            <header>
                <h2 id="report-group-{{ $loop->index }}">{{ $group['title'] }}</h2>
                <p>{{ $group['description'] }}</p>
            </header>
            <div class="reports-landing-grid">
                @foreach ($group['slugs'] as $slug)
                    @continue(! $reportsBySlug->has($slug))
                    @php($item = $reportsBySlug->get($slug))
                    <article class="report-landing-card card">
                        <span class="report-landing-card__icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['name'], 8, 2)) }}</span>
                        <h3>{{ $item['name'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                        <a class="btn btn-primary" href="{{ route($item['route']) }}">Buka Laporan</a>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
