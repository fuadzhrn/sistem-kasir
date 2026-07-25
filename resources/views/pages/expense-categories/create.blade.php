@extends('layouts.app')

@section('title', 'Tambah Kategori Pengeluaran')
@section('page-title', 'Tambah Kategori Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expense-categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Tambah Kategori Pengeluaran',
        'description' => 'Kategori baru langsung tersedia untuk seluruh cabang setelah disimpan.',
        'eyebrow' => 'Master Pengeluaran',
        'breadcrumbs' => [
            ['label' => 'Kategori Pengeluaran', 'url' => route('expense-categories.index')],
            ['label' => 'Tambah'],
        ],
    ])
    @include('pages.expense-categories.sections.form', ['expenseCategory' => null])
@endsection
