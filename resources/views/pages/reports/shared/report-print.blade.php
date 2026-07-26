@extends('layouts.print')
@section('title', $report['title'])
@section('content')
<article class="report-print report-print--{{ $report['orientation'] }}">
@include('pages.reports.shared.report-print-toolbar')
@include('pages.reports.shared.report-print-header')
@include('pages.reports.shared.report-summary')
@include('pages.reports.shared.report-table')
</article>
@endsection
