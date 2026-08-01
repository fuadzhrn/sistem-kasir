@extends('layouts.app')

@section('title', $viewer->isOwner() ? 'Audit Aktivitas' : 'Aktivitas Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/activities.css') }}">
@endpush

@php
    $dateFrom = filled($filters['date_from'] ?? null)
        ? \Carbon\CarbonImmutable::parse($filters['date_from'])->locale('id')->translatedFormat('d M Y')
        : null;
    $dateTo = filled($filters['date_to'] ?? null)
        ? \Carbon\CarbonImmutable::parse($filters['date_to'])->locale('id')->translatedFormat('d M Y')
        : null;
    $periodLabel = match (true) {
        $dateFrom && $dateTo => $dateFrom.' – '.$dateTo,
        (bool) $dateFrom => 'Mulai '.$dateFrom,
        (bool) $dateTo => 'Sampai '.$dateTo,
        default => 'Semua periode',
    };
    $selectedBranch = $viewer->isOwner() && filled($filters['branch'] ?? null)
        ? $branches->firstWhere('id', (int) $filters['branch'])
        : null;
    $viewerBranchName = $viewer->relationLoaded('branch')
        ? ($viewer->branch?->name ?? 'Cabang tidak tersedia')
        : 'Cabang akun Anda';
    $branchLabel = $viewer->isOwner()
        ? ($selectedBranch?->name ?? 'Semua cabang')
        : $viewerBranchName;
@endphp

@section('content')
    <div class="activities-page" data-activities-page>
        @include('partials.page-header', [
            'eyebrow' => 'Keamanan dan ketertelusuran',
            'title' => $viewer->isOwner() ? 'Audit Aktivitas' : 'Aktivitas Cabang',
            'description' => 'Riwayat tindakan penting aplikasi yang bersifat hanya-baca.',
        ])

        @include('pages.activities.sections.activity-mobile-toolbar')
        @include('pages.activities.sections.activity-filter-summary')
        @include('pages.activities.sections.activity-summary')
        @include('pages.activities.sections.activity-filters')

        <section class="card activities-table-card" aria-labelledby="activity-table-title">
            <div class="activities-section-heading">
                <div>
                    <p class="eyebrow">Riwayat</p>
                    <h2 id="activity-table-title">Daftar aktivitas</h2>
                </div>
                <span class="badge badge-neutral">{{ number_format($activityLogs->total(), 0, ',', '.') }} catatan</span>
            </div>

            @if ($activityLogs->isEmpty())
                @include('pages.activities.sections.empty-state')
            @else
                @include('pages.activities.sections.activity-table')
                @include('pages.activities.sections.activity-timeline')
                <div class="table-pagination activities-pagination">
                    <span>Menampilkan {{ number_format($activityLogs->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($activityLogs->lastItem() ?? 0, 0, ',', '.') }} dari {{ number_format($activityLogs->total(), 0, ',', '.') }} aktivitas</span>
                    <nav class="pagination-buttons" aria-label="Pagination aktivitas">
                        @if ($activityLogs->onFirstPage())
                            <span class="pagination-button activities-pagination__wide" aria-disabled="true">‹ Sebelumnya</span>
                        @else
                            <a class="pagination-button activities-pagination__wide" href="{{ $activityLogs->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹ Sebelumnya</a>
                        @endif

                        <span class="pagination-button is-active" aria-current="page">{{ $activityLogs->currentPage() }}</span>

                        @if ($activityLogs->hasMorePages())
                            <a class="pagination-button activities-pagination__wide" href="{{ $activityLogs->nextPageUrl() }}" aria-label="Halaman berikutnya">Berikutnya ›</a>
                        @else
                            <span class="pagination-button activities-pagination__wide" aria-disabled="true">Berikutnya ›</span>
                        @endif
                    </nav>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/activities.js') }}" defer></script>
@endpush
