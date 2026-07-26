@extends('layouts.app')
@section('title', $report['title'])
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/reports.css') }}">@endpush
@section('content')
<div class="reports-page" data-report-page>
@include('pages.reports.shared.report-header')
@include('pages.reports.shared.report-filters')
@include('pages.reports.shared.report-summary')
<section class="report-results card">
    @if ($report['rows']->isEmpty())
        @include('pages.reports.shared.report-empty-state')
    @else
        @include('pages.reports.shared.report-table')
        @include('pages.reports.shared.report-pagination')
    @endif
</section>
</div>
@endsection
@push('scripts')<script type="module" src="{{ asset('assets/js/pages/reports.js') }}"></script>@endpush
