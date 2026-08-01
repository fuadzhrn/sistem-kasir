@extends('layouts.app')
@section('title', 'Satuan')
@section('page-title', 'Satuan')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/units.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Satuan Produk', 'description' => 'Kelola satuan jual yang digunakan bersama oleh seluruh cabang.', 'eyebrow' => 'Master Data Global'])
    @can('create', \App\Models\Unit::class)<div class="module-actions"><a class="btn btn-primary" href="{{ route('units.create') }}">Tambah Satuan</a></div>@endcan
    @include('pages.units.sections.unit-summary')
    @include('pages.units.sections.unit-filters')
    @include('pages.units.sections.unit-table')
    @include('pages.units.sections.status-modal')
    @include('pages.units.sections.delete-modal')
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/pages/units.js') }}" defer></script>
@endpush
