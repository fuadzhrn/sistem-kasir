@extends('layouts.app')

@section('title', 'Kategori Pengeluaran')
@section('page-title', 'Kategori Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expense-categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Kategori Pengeluaran',
        'description' => 'Master kategori global untuk pencatatan pengeluaran seluruh cabang.',
        'eyebrow' => 'Master Pengeluaran',
    ])
    <div class="module-actions">
        <a class="btn btn-primary" href="{{ route('expense-categories.create') }}">Tambah Kategori Pengeluaran</a>
    </div>
    @include('pages.expense-categories.sections.summary')
    @include('pages.expense-categories.sections.filters')
    @include('pages.expense-categories.sections.table')
    @include('pages.expense-categories.sections.status-modal')
    @include('pages.expense-categories.sections.delete-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/expense-categories.js') }}" defer></script>
@endpush
