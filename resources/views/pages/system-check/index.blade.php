@extends('layouts.app')

@section('title', 'Pemeriksaan Sistem')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/system-check.css') }}">
@endpush

@section('content')
    <section class="page-header">
        <p class="eyebrow">Khusus lingkungan lokal</p>
        <h1>Pemeriksaan Sistem</h1>
        <p class="page-header__description">
            Ringkasan aman untuk memverifikasi kesiapan teknis aplikasi tanpa menampilkan
            credential atau konfigurasi sensitif.
        </p>
    </section>

    <section class="system-check" aria-labelledby="system-check-heading">
        <div class="system-check__toolbar">
            <div>
                <h2 id="system-check-heading">Status lingkungan</h2>
                <p>
                    Diperiksa pada
                    <time datetime="{{ $checkedAt->toIso8601String() }}">
                        {{ $checkedAt->format('d-m-Y H:i:s T') }}
                    </time>
                </p>
            </div>

            <button class="button button--secondary" type="button" data-system-check-refresh>
                Periksa ulang
            </button>
        </div>

        <div class="system-check__grid">
            @foreach ($checks as $check)
                <article class="card system-check__item">
                    <div class="card__body">
                        <div class="system-check__item-heading">
                            <h3>{{ $check['label'] }}</h3>
                            <span class="badge {{ $check['status'] ? 'badge--success' : 'badge--danger' }}">
                                {{ $check['status'] ? 'Baik' : 'Perlu diperiksa' }}
                            </span>
                        </div>
                        <p>{{ $check['value'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="system-check__client-time">
            Waktu browser: <span data-client-time>Memuat…</span>
        </p>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/system-check.js') }}" defer></script>
@endpush
