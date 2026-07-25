@extends('layouts.app')
@section('title', 'Edit Satuan')
@section('page-title', 'Edit Satuan')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/pages/units.css') }}">@endpush
@section('content')
    @include('partials.page-header', ['title' => 'Edit Satuan', 'description' => 'Perbarui identitas satuan tanpa mengubah statusnya.', 'eyebrow' => 'Master Data Global'])
    <div class="module-actions"><a class="btn btn-secondary" href="{{ route('units.show', $unit) }}">Batal</a></div>
    <section class="card form-card">
        <div class="alert alert-info">Data ini digunakan bersama oleh seluruh cabang.</div>
        <form method="POST" action="{{ route('units.update', $unit) }}">@csrf @method('PUT') @include('pages.units.sections.unit-form', ['unit' => $unit])</form>
    </section>
@endsection
