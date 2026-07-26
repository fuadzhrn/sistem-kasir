@php
    $summaryCards = [
        ['label' => 'Nota Selesai Hari Ini', 'value' => $dashboard['today']['completed'], 'variant' => 'success'],
        ['label' => 'Nota Dibatalkan Hari Ini', 'value' => $dashboard['today']['voided'], 'variant' => 'danger'],
        ['label' => 'Total Nota Dibuat Hari Ini', 'value' => $dashboard['today']['total'], 'variant' => 'info'],
    ];
@endphp

<section class="cashier-dashboard__summary" aria-labelledby="cashier-summary-title">
    <h2 id="cashier-summary-title">Ringkasan Hari Ini</h2>
    <div class="cashier-dashboard__summary-grid">
        @foreach ($summaryCards as $card)
            <article class="cashier-dashboard__summary-card card is-{{ $card['variant'] }}">
                <p>{{ $card['label'] }}</p>
                <strong>{{ number_format($card['value'], 0, ',', '.') }}</strong>
            </article>
        @endforeach
    </div>
</section>
