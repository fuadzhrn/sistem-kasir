@extends('layouts.app')

@section('title', $title)
@section('page-title', 'Pemeriksaan Otorisasi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/authorization-check.css') }}">
@endpush

@section('content')
    <section class="card authorization-result">
        <span class="badge badge-success">Diizinkan</span>
        <h2>{{ $title }}</h2>
        <p>{{ $message }}</p>
        <a class="btn btn-primary" href="{{ route('authorization-check.index') }}">Kembali ke pemeriksaan</a>
    </section>
@endsection
