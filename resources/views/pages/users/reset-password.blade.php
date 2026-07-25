@extends('layouts.app')

@section('title', 'Reset Kata Sandi')
@section('page-title', 'Reset Kata Sandi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/users.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Reset Kata Sandi',
        'description' => 'Tetapkan kata sandi baru tanpa melihat atau meminta password lama pengguna.',
        'eyebrow' => 'Khusus Owner',
        'breadcrumbs' => [
            ['label' => 'Pengguna', 'url' => route('users.index')],
            ['label' => $user->name, 'url' => route('users.show', $user)],
            ['label' => 'Reset Kata Sandi'],
        ],
    ])

    <section class="card form-card password-reset-card">
        <div class="password-target">
            <span>Pengguna tujuan</span>
            <strong>{{ $user->name }}</strong>
            <small>{{ '@'.$user->username }} · {{ $user->role?->name }}{{ $user->branch ? ' · '.$user->branch->name : '' }}</small>
        </div>

        <form action="{{ route('users.password.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label class="form-label" for="password">Kata Sandi Baru <span class="form-required">*</span></label>
                    <div class="password-field">
                        <input class="form-control @error('password') is-error @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="password" aria-controls="password" aria-label="Tampilkan kata sandi baru"><span data-password-toggle-label>Tampilkan</span></button>
                    </div>
                    @error('password')<span class="form-error">{{ $message }}</span>@enderror
                    <span class="form-help">Minimal delapan karakter, huruf besar, huruf kecil, dan angka.</span>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi <span class="form-required">*</span></label>
                    <div class="password-field">
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi"><span data-password-toggle-label>Tampilkan</span></button>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <a class="btn btn-secondary" href="{{ route('users.show', $user) }}">Batal</a>
                <button class="btn btn-danger" type="submit">Reset Kata Sandi</button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
@endpush
