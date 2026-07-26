@extends('layouts.app')

@section('title', 'Detail Aktivitas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/activities.css') }}">
@endpush

@section('content')
    <div class="activities-page">
        @include('pages.activities.sections.detail-header')

        <div class="activities-detail-grid">
            @include('pages.activities.sections.actor')
            @include('pages.activities.sections.reference')
            @include('pages.activities.sections.security-context')
            @include('pages.activities.sections.metadata')
        </div>
    </div>
@endsection
