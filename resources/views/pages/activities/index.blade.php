@extends('layouts.app')

@section('title', $viewer->isOwner() ? 'Audit Aktivitas' : 'Aktivitas Cabang')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/activities.css') }}">
@endpush

@section('content')
    <div class="activities-page" data-activities-page>
        @include('partials.page-header', [
            'eyebrow' => 'Keamanan dan ketertelusuran',
            'title' => $viewer->isOwner() ? 'Audit Aktivitas' : 'Aktivitas Cabang',
            'description' => 'Riwayat tindakan penting aplikasi yang bersifat hanya-baca.',
        ])

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
                <div class="table-pagination activities-pagination">
                    <span>Menampilkan {{ $activityLogs->firstItem() ?? 0 }}–{{ $activityLogs->lastItem() ?? 0 }} dari {{ $activityLogs->total() }} aktivitas</span>
                    <nav class="pagination-buttons" aria-label="Pagination aktivitas">
                        @if ($activityLogs->onFirstPage())
                            <span class="pagination-button" aria-disabled="true">‹</span>
                        @else
                            <a class="pagination-button" href="{{ $activityLogs->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
                        @endif

                        <span class="pagination-button is-active" aria-current="page">{{ $activityLogs->currentPage() }}</span>

                        @if ($activityLogs->hasMorePages())
                            <a class="pagination-button" href="{{ $activityLogs->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
                        @else
                            <span class="pagination-button" aria-disabled="true">›</span>
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
