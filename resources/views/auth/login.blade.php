@extends('layouts.auth')

@section('title', 'Masuk')
@section('body-class', 'auth-login-body')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth/login.css') }}">
@endpush

@section('auth-page')
    @php
        $selectedRole = old('login_role');
        $roleLabels = [
            'owner' => 'Owner',
            'admin' => 'Admin Cabang',
            'cashier' => 'Kasir',
        ];
        $submitLabel = isset($roleLabels[$selectedRole])
            ? 'Masuk sebagai '.$roleLabels[$selectedRole]
            : 'Pilih Jenis Akun Terlebih Dahulu';
    @endphp

    <div class="auth-page" data-auth-page>
        <section class="auth-brand-panel" aria-labelledby="auth-brand-title">
            <div class="auth-brand-panel__content">
                <div class="auth-brand-panel__identity">
                    <span class="auth-brand-panel__logo" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M19.5 4.5c-6.3.3-10.1 2.4-11.7 6.2-1 2.4-.5 4.8.2 6.3 1.3-3.1 3.6-5.7 7.1-7.8-3 2.7-4.8 5.7-5.5 9.1 1.8.9 4.3.8 6.3-.6 3.8-2.6 4.1-7.7 3.6-13.2Z"/>
                        </svg>
                    </span>
                    <span>
                        <small>Sistem terpercaya</small>
                        <strong>{{ config('app.name') }}</strong>
                    </span>
                </div>

                <div class="auth-brand-panel__intro">
                    <p class="auth-brand-panel__eyebrow">Sistem Manajemen Toko</p>
                    <h1 id="auth-brand-title">Kelola toko lebih mudah dalam satu tempat.</h1>
                    <p>
                        Pantau penjualan, stok, pengeluaran, dan seluruh cabang melalui
                        alur kerja yang rapi dan terpusat.
                    </p>
                </div>

                <ul class="auth-benefits" aria-label="Manfaat utama aplikasi">
                    <li><span aria-hidden="true">✓</span> Stok tercatat otomatis dan mudah diperiksa.</li>
                    <li><span aria-hidden="true">✓</span> Penjualan setiap cabang lebih mudah dipantau.</li>
                    <li><span aria-hidden="true">✓</span> Laporan toko tersusun lebih teratur.</li>
                </ul>
            </div>

            <div class="auth-brand-panel__decoration" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </section>

        <section class="auth-login-panel" aria-labelledby="login-title">
            <div class="auth-mobile-brand" aria-label="{{ config('app.name') }}">
                <span class="auth-mobile-brand__logo" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M19.5 4.5c-6.3.3-10.1 2.4-11.7 6.2-1 2.4-.5 4.8.2 6.3 1.3-3.1 3.6-5.7 7.1-7.8-3 2.7-4.8 5.7-5.5 9.1 1.8.9 4.3.8 6.3-.6 3.8-2.6 4.1-7.7 3.6-13.2Z"/>
                    </svg>
                </span>
                <strong>{{ config('app.name') }}</strong>
            </div>

            <div class="auth-login-panel__inner">
                <section class="auth-login-card">
                    @include('partials.alert')

                    <header class="auth-login-heading">
                        <p class="auth-login-heading__eyebrow">Akses akun</p>
                        <h2 id="login-title">Selamat Datang</h2>
                        <p>Pilih jenis akun Anda untuk masuk.</p>
                        <p class="auth-login-heading__context" data-login-context aria-live="polite">
                            {{ isset($roleLabels[$selectedRole]) ? 'Masuk sebagai '.$roleLabels[$selectedRole] : 'Belum ada jenis akun yang dipilih' }}
                        </p>
                    </header>

                    <form class="auth-form auth-login-form" action="{{ route('login.store') }}" method="POST" data-login-form>
                        @csrf

                        <fieldset class="auth-role-selector" @error('login_role') aria-describedby="login-role-error" @enderror>
                            <legend>Pilih Jenis Akun <span aria-hidden="true">*</span></legend>

                            <div class="auth-role-selector__grid">
                                <label class="auth-role-option">
                                    <input type="radio" name="login_role" value="owner" @checked($selectedRole === 'owner') aria-describedby="owner-role-description">
                                    <span class="auth-role-card">
                                        <span class="auth-role-card__top">
                                            <span class="auth-role-card__icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false">
                                                    <path d="M12 3.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm-7 16c.5-4 3-6 7-6s6.5 2 7 6v1H5v-1Z"/>
                                                </svg>
                                            </span>
                                            <span class="auth-role-card__selected"><span aria-hidden="true">✓</span> Dipilih</span>
                                        </span>
                                        <strong class="auth-role-card__title">Owner</strong>
                                        <span class="auth-role-card__description" id="owner-role-description">
                                            Memantau seluruh cabang, penjualan, stok, pengeluaran, dan keuntungan.
                                        </span>
                                    </span>
                                </label>

                                <label class="auth-role-option">
                                    <input type="radio" name="login_role" value="admin" @checked($selectedRole === 'admin') aria-describedby="admin-role-description">
                                    <span class="auth-role-card">
                                        <span class="auth-role-card__top">
                                            <span class="auth-role-card__icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false">
                                                    <path d="M4 20V7l8-4 8 4v13h-6v-5h-4v5H4Zm3-9h3V8H7v3Zm7 0h3V8h-3v3Z"/>
                                                </svg>
                                            </span>
                                            <span class="auth-role-card__selected"><span aria-hidden="true">✓</span> Dipilih</span>
                                        </span>
                                        <strong class="auth-role-card__title">Admin Cabang</strong>
                                        <span class="auth-role-card__description" id="admin-role-description">
                                            Mengelola produk, stok, barang masuk, pengeluaran, dan kegiatan cabang.
                                        </span>
                                    </span>
                                </label>

                                <label class="auth-role-option">
                                    <input type="radio" name="login_role" value="cashier" @checked($selectedRole === 'cashier') aria-describedby="cashier-role-description">
                                    <span class="auth-role-card">
                                        <span class="auth-role-card__top">
                                            <span class="auth-role-card__icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false">
                                                    <path d="M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm1 3v4h12V7H6Zm0 7v2h3v-2H6Zm5 0v2h3v-2h-3Zm5 0v5h2v-5h-2Zm-10 4v1h8v-1H6Z"/>
                                                </svg>
                                            </span>
                                            <span class="auth-role-card__selected"><span aria-hidden="true">✓</span> Dipilih</span>
                                        </span>
                                        <strong class="auth-role-card__title">Kasir</strong>
                                        <span class="auth-role-card__description" id="cashier-role-description">
                                            Membuat transaksi, menerima pembayaran, dan mencetak struk.
                                        </span>
                                    </span>
                                </label>
                            </div>

                            @error('login_role')
                                <span class="form-error auth-error" id="login-role-error" role="alert">{{ $message }}</span>
                            @enderror
                        </fieldset>

                        <div class="form-group">
                            <label class="form-label" for="login">Username atau Email</label>
                            <input
                                class="form-control @error('login') is-error @enderror"
                                id="login"
                                name="login"
                                type="text"
                                value="{{ old('login') }}"
                                placeholder="Masukkan username atau email Anda"
                                autocomplete="username"
                                maxlength="255"
                                required
                                @error('login') aria-invalid="true" aria-describedby="login-error" @enderror
                            >
                            @error('login')
                                <span class="form-error auth-error" id="login-error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Kata Sandi</label>
                            <div class="password-field auth-password-field">
                                <input
                                    class="form-control @error('password') is-error @enderror"
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Masukkan password Anda"
                                    autocomplete="current-password"
                                    required
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                >
                                <button
                                    class="password-toggle auth-password-toggle"
                                    type="button"
                                    data-password-toggle
                                    data-password-target="password"
                                    aria-controls="password"
                                    aria-label="Tampilkan kata sandi"
                                    aria-pressed="false"
                                >
                                    <svg data-password-icon-show viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <path d="M12 5c5 0 8.8 4.2 10 7-1.2 2.8-5 7-10 7S3.2 14.8 2 12c1.2-2.8 5-7 10-7Zm0 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm0 2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/>
                                    </svg>
                                    <svg data-password-icon-hide viewBox="0 0 24 24" focusable="false" aria-hidden="true" hidden>
                                        <path d="m4.3 3 16.7 16.7-1.3 1.3-3-3A10.6 10.6 0 0 1 12 19c-5 0-8.8-4.2-10-7a15 15 0 0 1 3.5-4.7L3 4.3 4.3 3Zm4 7.1A4 4 0 0 0 14 15.7l-5.7-5.6ZM12 5c5 0 8.8 4.2 10 7a14.8 14.8 0 0 1-2.5 3.7l-2.2-2.2A5.5 5.5 0 0 0 10.5 6l-.8-.8c.8-.2 1.5-.2 2.3-.2Z"/>
                                    </svg>
                                    <span data-password-toggle-label>Tampilkan</span>
                                </button>
                            </div>
                            @error('password')
                                <span class="form-error auth-error" id="password-error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <label class="auth-remember">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            <span>Ingat saya</span>
                        </label>

                        <button
                            class="btn btn-primary btn-lg auth-submit auth-submit-button"
                            type="submit"
                            data-login-submit
                            data-selected-role="{{ $selectedRole }}"
                        >
                            <span data-login-submit-label>{{ $submitLabel }}</span>
                            <span class="auth-submit-button__loader" data-login-loader aria-hidden="true"></span>
                        </button>
                    </form>

                    <div class="auth-help-text">
                        <strong>Lupa username atau password?</strong>
                        <p>Silakan hubungi pemilik toko atau Admin yang bertanggung jawab.</p>
                    </div>
                </section>

                <footer class="auth-login-footer">
                    <p>© {{ now()->year }} {{ config('app.name') }}. Akses khusus pengguna terdaftar.</p>
                </footer>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/components/password-toggle.js') }}" defer></script>
    <script src="{{ asset('assets/js/pages/auth/login.js') }}" defer></script>
@endpush
