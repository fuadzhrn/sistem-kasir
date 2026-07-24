@php
    $alertIcon = [
        'success' => '✓',
        'warning' => '!',
        'danger' => '×',
        'info' => 'i',
    ];
@endphp

@if (isset($type, $title, $message))
    <div class="alert alert-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}" data-alert>
        <span class="alert__icon" aria-hidden="true">{{ $alertIcon[$type] ?? 'i' }}</span>
        <div class="alert__content">
            <h4 class="alert__title">{{ $title }}</h4>
            <p class="alert__message">{{ $message }}</p>
        </div>
        @if ($dismissible ?? false)
            <button class="alert__close" type="button" data-alert-dismiss aria-label="Tutup pemberitahuan">×</button>
        @endif
    </div>
@else
    @if (session('status'))
        <div class="alert alert-success" role="status" data-alert>
            <span class="alert__icon" aria-hidden="true">✓</span>
            <div class="alert__content">
                <h4 class="alert__title">Berhasil</h4>
                <p class="alert__message">{{ session('status') }}</p>
            </div>
            <button class="alert__close" type="button" data-alert-dismiss aria-label="Tutup pemberitahuan">×</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert" data-alert>
            <span class="alert__icon" aria-hidden="true">×</span>
            <div class="alert__content">
                <h4 class="alert__title">Data belum valid</h4>
                <p class="alert__message">Periksa kembali data yang dimasukkan.</p>
            </div>
        </div>
    @endif
@endif
