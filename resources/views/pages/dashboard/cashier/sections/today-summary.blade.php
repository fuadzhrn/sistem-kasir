@php
    $summaryCards = [
        ['label' => 'Transaksi Selesai', 'value' => $dashboard['today']['completed'], 'variant' => 'success', 'short' => 'SL'],
        ['label' => 'Transaksi Dibatalkan', 'value' => $dashboard['today']['voided'], 'variant' => 'danger', 'short' => 'BT'],
        ['label' => 'Total Transaksi', 'value' => $dashboard['today']['total'], 'variant' => 'info', 'short' => 'TR'],
    ];
@endphp

<section class="cashier-dashboard__summary" aria-labelledby="cashier-summary-title">
    <h2 id="cashier-summary-title">Ringkasan Hari Ini</h2>
    <div class="cashier-dashboard__summary-grid">
        @foreach ($summaryCards as $card)
            <article class="cashier-dashboard__summary-card card is-{{ $card['variant'] }}">
                <span class="cashier-dashboard__summary-icon" aria-hidden="true">{{ $card['short'] }}</span>
                <div>
                    <p>{{ $card['label'] }}</p>
                    <strong>{{ number_format($card['value'], 0, ',', '.') }}</strong>
                    <small>Hari ini</small>
                </div>
            </article>
        @endforeach
    </div>
</section>
