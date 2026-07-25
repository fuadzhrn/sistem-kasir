@extends('layouts.app')

@section('title', 'Edit Kategori Pengeluaran')
@section('page-title', 'Edit Kategori Pengeluaran')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/expense-categories.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Edit Kategori Pengeluaran',
        'description' => 'Perubahan nama tetap mempertahankan hubungan dengan histori pengeluaran.',
        'eyebrow' => 'Master Pengeluaran',
        'breadcrumbs' => [
            ['label' => 'Kategori Pengeluaran', 'url' => route('expense-categories.index')],
            ['label' => 'Edit'],
        ],
    ])
    @include('pages.expense-categories.sections.form', ['expenseCategory' => $expenseCategory])
@endsection
