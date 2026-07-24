@extends('layouts.app')

@section('title', 'Design System')
@section('page-title', 'Design System')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/design-system.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'eyebrow' => 'Tahap 2',
        'title' => 'Design System',
        'description' => 'Fondasi antarmuka internal untuk menjaga tampilan aplikasi tetap konsisten, mudah dipahami, dan nyaman digunakan pada komputer toko.',
        'breadcrumbs' => [
            ['label' => 'Beranda', 'url' => url('/')],
            ['label' => 'Design System'],
        ],
        'actions' => [
            ['label' => 'Demo toast', 'class' => 'btn btn-secondary', 'toast' => 'success'],
            ['label' => 'Demo modal', 'class' => 'btn btn-primary', 'modal' => 'modal-confirm'],
        ],
    ])

    <div class="design-system">
        @include('pages.design-system.sections.introduction')
        @include('pages.design-system.sections.colors')
        @include('pages.design-system.sections.typography')
        @include('pages.design-system.sections.buttons')
        @include('pages.design-system.sections.forms')
        @include('pages.design-system.sections.cards')
        @include('pages.design-system.sections.badges')
        @include('pages.design-system.sections.tables')
        @include('pages.design-system.sections.alerts')
        @include('pages.design-system.sections.modal')
        @include('pages.design-system.sections.toast')
        @include('pages.design-system.sections.empty-state')
        @include('pages.design-system.sections.loading-state')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/design-system.js') }}" defer></script>
@endpush
