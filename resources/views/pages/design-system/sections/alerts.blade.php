<section class="ds-section" id="alerts" aria-labelledby="alerts-title">
    <div class="ds-section__header">
        <div>
            <span class="ds-section__number">09</span>
            <h2 id="alerts-title">Alert</h2>
            <p>Pemberitahuan kontekstual dengan judul, pesan, simbol, role, dan opsi tutup.</p>
        </div>
    </div>

    <div class="alert-grid">
        @include('partials.alert', [
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => 'Tindakan contoh berhasil diproses.',
            'dismissible' => true,
        ])
        @include('partials.alert', [
            'type' => 'warning',
            'title' => 'Perlu perhatian',
            'message' => 'Periksa kembali informasi contoh sebelum melanjutkan.',
        ])
        @include('partials.alert', [
            'type' => 'danger',
            'title' => 'Tidak dapat diproses',
            'message' => 'Terjadi kesalahan pada demonstrasi tampilan.',
            'dismissible' => true,
        ])
        @include('partials.alert', [
            'type' => 'info',
            'title' => 'Informasi',
            'message' => 'Komponen ini hanya menampilkan data statis.',
        ])
    </div>
</section>
