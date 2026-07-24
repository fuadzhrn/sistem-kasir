@extends('layouts.app')

@section('title', 'Inisialisasi Aplikasi')
@section('page-title', 'Inisialisasi Aplikasi')

@section('content')
    <section class="page-header">
        <p class="eyebrow">Tahap 1</p>
        <h2>Fondasi aplikasi siap dikembangkan</h2>
        <p class="page-header__description">
            Proyek Laravel telah disiapkan dengan struktur Blade, CSS, dan JavaScript modular.
            Belum ada fitur bisnis pada tahap ini.
        </p>
    </section>

    <section class="card">
        <div class="card__body">
            <h3>Pemeriksaan lingkungan lokal</h3>
            <p>
                Gunakan halaman pemeriksaan sistem untuk memastikan runtime, database, storage,
                dan asset dasar tersedia.
            </p>

            @if (app()->environment('local'))
                <a class="button button--primary" href="{{ route('system-check.index') }}">
                    Buka pemeriksaan sistem
                </a>
            @endif
        </div>
    </section>
@endsection
