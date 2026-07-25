@extends('layouts.app')

@section('title', 'Kelola Kata Sandi')
@section('page-title', 'Kelola Kata Sandi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth/change-password.css') }}">
@endpush

@section('content')
    @include('partials.page-header', [
        'title' => 'Kelola Kata Sandi Pengguna',
        'description' => 'Tetapkan kata sandi baru untuk akun Owner, Admin, atau Kasir.',
        'eyebrow' => 'Khusus Owner',
        'breadcrumbs' => [
            ['label' => 'Akun Saya', 'url' => route('account.index')],
            ['label' => 'Kelola Kata Sandi'],
        ],
    ])

    <section class="card password-card" aria-labelledby="password-form-title">
        <div class="password-card__heading">
            <h3 id="password-form-title">Reset kata sandi pengguna</h3>
            <p>Pilih akun tujuan. Password lama tidak ditampilkan dan akan langsung diganti dengan password baru.</p>
        </div>

        <form class="password-form" action="{{ route('account.password.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="user_id">Akun yang Diubah</label>
                <select class="form-control @error('user_id') is-error @enderror" id="user_id" name="user_id" required>
                    <option value="">Pilih akun</option>
                    @foreach ($users as $targetUser)
                        <option value="{{ $targetUser->id }}" @selected((string) old('user_id') === (string) $targetUser->id)>
                            {{ $targetUser->name }} — {{ '@'.$targetUser->username }}
                            ({{ $targetUser->role?->name ?? 'Role tidak tersedia' }}{{ $targetUser->branch ? ' / '.$targetUser->branch->name : '' }})
                            {{ $targetUser->is_active ? '' : '— Nonaktif' }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="current_password">Kata Sandi Owner Saat Ini</label>
                <div class="password-field">
                    <input class="form-control @error('current_password') is-error @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-password-toggle data-password-target="current_password" aria-controls="current_password" aria-label="Tampilkan kata sandi Owner saat ini" aria-pressed="false">
                        <span data-password-toggle-label>Tampilkan</span>
                    </button>
                </div>
                @error('current_password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
                <span class="form-help">Diperlukan untuk mengonfirmasi bahwa perubahan dilakukan oleh Owner.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi Baru</label>
                <div class="password-field">
                    <input class="form-control @error('password') is-error @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-password-toggle data-password-target="password" aria-controls="password" aria-label="Tampilkan kata sandi baru" aria-pressed="false">
                        <span data-password-toggle-label>Tampilkan</span>
                    </button>
                </div>
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <div class="password-field">
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-password-toggle data-password-target="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false">
                        <span data-password-toggle-label>Tampilkan</span>
                    </button>
                </div>
            </div>

            <div class="password-form__actions">
                <a class="btn btn-secondary" href="{{ route('account.index') }}">Batal</a>
                <button class="btn btn-primary" type="submit">Ganti Kata Sandi</button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
@endpush
