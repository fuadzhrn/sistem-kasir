<section class="card settings-section" id="metode-pembayaran">
    <div class="card__header settings-section__header">
        <div class="settings-section__heading">
            <span class="settings-section__number">05</span>
            <div>
                <h2>Metode Pembayaran</h2>
                <p>Ringkasan metode yang tersedia untuk transaksi baru.</p>
            </div>
        </div>
        <a class="btn btn-secondary btn-sm" href="{{ route('payment-methods.index') }}">Kelola Metode Pembayaran</a>
    </div>
    <div class="card__body">
        <div class="payment-summary__counts">
            <div><strong>{{ $paymentMethodSummary['active'] }}</strong><span>Aktif</span></div>
            <div><strong>{{ $paymentMethodSummary['inactive'] }}</strong><span>Nonaktif</span></div>
        </div>
        <div class="payment-summary__list">
            @forelse ($paymentMethodSummary['methods'] as $method)
                <article class="payment-summary__item">
                    <div><strong>{{ $method->name }}</strong><small>{{ $method->code }} · {{ str($method->type)->replace('_', ' ')->title() }}</small></div>
                    <span>Urutan {{ $method->sort_order }}</span>
                    <span class="badge badge-success">Aktif</span>
                </article>
            @empty
                <p class="settings-empty">Belum ada metode pembayaran aktif.</p>
            @endforelse
        </div>
        <p class="settings-note">Daftar ini bersumber langsung dari modul Metode Pembayaran, bukan disalin ke settings.</p>
    </div>
</section>
