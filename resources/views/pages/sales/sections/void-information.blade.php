@if ($sale->isVoided() && $sale->saleVoid)
    <section class="card sale-void-information" aria-label="Informasi pembatalan transaksi">
        <div class="sale-void-information__heading">
            <div>
                <span>Riwayat Pembatalan</span>
                <h2>Transaksi Dibatalkan</h2>
            </div>
            <span class="badge badge-danger">Dibatalkan</span>
        </div>
        <dl>
            <div><dt>Alasan</dt><dd>{{ $sale->saleVoid->reason }}</dd></div>
            <div><dt>Dibatalkan oleh</dt><dd>{{ $sale->saleVoid->voider?->name ?? 'Pengguna historis' }}</dd></div>
            <div><dt>Waktu pembatalan</dt><dd>{{ $sale->saleVoid->voided_at?->locale('id')->translatedFormat('d F Y, H.i') ?? 'Tidak tersedia' }}</dd></div>
            @if ($sale->paymentMethod?->type !== 'cash')
                <div><dt>Konfirmasi refund</dt><dd>{{ $sale->saleVoid->refund_confirmed ? 'Penanganan refund manual dikonfirmasi' : 'Tidak tercatat' }}</dd></div>
            @endif
        </dl>
    </section>
@endif
