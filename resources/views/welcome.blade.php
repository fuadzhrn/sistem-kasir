@extends('layouts.app')

@section('title', 'Inisialisasi Aplikasi')

@section('content')
    <section class="page-header">
        <p class="eyebrow">Tahap 1</p>
        <h1>Fondasi aplikasi siap dikembangkan</h1>
        <p class="page-header__description">
            Proyek Laravel telah disiapkan dengan struktur Blade, CSS, dan JavaScript modular.
            Belum ada fitur bisnis pada tahap ini.
        </p>
    </section>

    <section class="card">
        <div class="card__body">
            <h2>Pemeriksaan lingkungan lokal</h2>
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
