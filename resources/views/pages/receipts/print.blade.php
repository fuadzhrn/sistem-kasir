@extends('layouts.print')

@section('title', 'Struk '.$receipt['invoice_number'])

@section('content')
    <section class="receipt-toolbar print-hidden" aria-label="Kontrol cetak struk">
        <div class="receipt-toolbar__actions">
            <a class="receipt-toolbar__button receipt-toolbar__button--secondary" href="{{ route('sales.show', ['sale' => request()->route('sale')]) }}">
                Kembali ke Detail
            </a>
            <label class="receipt-toolbar__paper">
                <span>Ukuran Kertas</span>
                <select data-receipt-paper-select>
                    <option value="58">58 mm</option>
                    <option value="80" selected>80 mm</option>
                </select>
            </label>
            <button class="receipt-toolbar__button" type="button" data-receipt-print-button>
                Cetak Struk
            </button>
        </div>
        <div class="receipt-toolbar__instructions">
            <strong>Petunjuk dialog browser</strong>
            <span>Pilih printer thermal dan ukuran 58 mm atau 80 mm.</span>
            <span>Gunakan skala 100% serta margin None atau Minimum.</span>
            <span>Matikan Headers and Footers browser.</span>
        </div>
        <p class="receipt-toolbar__status" data-receipt-print-status aria-live="polite">
            Menyiapkan dialog cetak…
        </p>
        <noscript>
            <p class="receipt-toolbar__noscript">JavaScript tidak aktif. Gunakan Ctrl+P untuk membuka dialog cetak.</p>
        </noscript>
    </section>

    <article
        class="receipt receipt-paper--{{ config('receipt.default_paper_width', '80') }}"
        data-receipt-paper
        data-receipt-auto-print="true"
    >
        @if ($isCopy)
            <div class="receipt-copy-label">SALINAN</div>
        @endif

        @if ($receipt['status'] === \App\Models\Sale::STATUS_VOIDED)
            <div class="receipt-status receipt-status--voided">TRANSAKSI DIBATALKAN</div>
        @elseif ($receipt['status'] === \App\Models\Sale::STATUS_VOID_REQUESTED)
            <div class="receipt-status receipt-status--requested">MENUNGGU PEMBATALAN</div>
        @elseif (! in_array($receipt['status'], \App\Models\Sale::statuses(), true))
            <div class="receipt-status receipt-status--unknown">STATUS TIDAK DIKENAL</div>
        @endif

        <header class="receipt__header">
            <h1>{{ $receipt['store_name'] }}</h1>
            <p class="receipt__branch">{{ $receipt['branch_name'] }}</p>
            @if ($receipt['branch_address'])
                <p>{{ $receipt['branch_address'] }}</p>
            @endif
            @if ($receipt['branch_phone'])
                <p>Telp. {{ $receipt['branch_phone'] }}</p>
            @endif
        </header>

        <div class="receipt__separator" aria-hidden="true"></div>

        <dl class="receipt__meta">
            <div><dt>Nomor Nota</dt><dd>{{ $receipt['invoice_number'] }}</dd></div>
            <div><dt>Tanggal</dt><dd>{{ $receipt['transaction_date']->locale('id')->translatedFormat('d/m/Y H:i') }}</dd></div>
            <div><dt>Kasir</dt><dd>{{ $receipt['cashier_name'] }}</dd></div>
            <div><dt>Status</dt><dd>{{ $receipt['status_label'] }}</dd></div>
        </dl>

        <div class="receipt__separator" aria-hidden="true"></div>

        <section class="receipt__items" aria-label="Item transaksi">
            @foreach ($receipt['items'] as $item)
                <article class="receipt-line">
                    <p class="receipt-line__name">
                        {{ $item['name'] }}@if ($item['size']) — {{ $item['size'] }}@endif
                    </p>
                    <p class="receipt-line__code">{{ $item['code'] }}</p>
                    <div class="receipt-line__calculation">
                        <span>
                            {{ \App\Support\Format\Quantity::format($item['quantity']) }}
                            {{ $item['unit_name'] }}
                            × {{ \App\Support\Format\Rupiah::format($item['selling_price']) }}
                        </span>
                        <strong>{{ \App\Support\Format\Rupiah::format($item['subtotal']) }}</strong>
                    </div>
                    @if ((float) $item['discount_amount'] > 0)
                        <p class="receipt-line__discount">Diskon item {{ \App\Support\Format\Rupiah::format($item['discount_amount']) }}</p>
                    @endif
                </article>
            @endforeach
        </section>

        <div class="receipt__separator" aria-hidden="true"></div>

        <dl class="receipt__summary">
            <div><dt>Subtotal</dt><dd>{{ \App\Support\Format\Rupiah::format($receipt['subtotal']) }}</dd></div>
            <div><dt>Diskon</dt><dd>− {{ \App\Support\Format\Rupiah::format($receipt['discount_amount']) }}</dd></div>
            <div class="receipt__total"><dt>Total</dt><dd>{{ \App\Support\Format\Rupiah::format($receipt['total']) }}</dd></div>
            <div><dt>Metode</dt><dd>{{ $receipt['payment_method_name'] }}</dd></div>
            <div><dt>Uang Diterima</dt><dd>{{ \App\Support\Format\Rupiah::format($receipt['amount_paid']) }}</dd></div>
            <div><dt>Kembalian</dt><dd>{{ \App\Support\Format\Rupiah::format($receipt['change_amount']) }}</dd></div>
        </dl>

        @if ($receipt['notes'])
            <div class="receipt__separator" aria-hidden="true"></div>
            <p class="receipt__notes">Catatan: {{ $receipt['notes'] }}</p>
        @endif

        <div class="receipt__separator" aria-hidden="true"></div>

        <footer class="receipt__footer">
            <p>{{ $receipt['closing_message'] }}</p>
        </footer>
    </article>
@endsection
