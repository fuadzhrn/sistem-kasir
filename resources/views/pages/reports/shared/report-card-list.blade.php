@php
    $headingKeyByReport = [
        'sales' => 'invoice',
        'receipts' => 'invoice',
        'expenses' => 'description',
        'stocks' => 'product',
        'stock-receipts' => 'number',
        'top-products' => 'product',
        'cashiers' => 'cashier',
        'price-histories' => 'product',
        'sale-voids' => 'invoice',
    ];
    $eyebrowKeyByReport = [
        'sales' => 'date',
        'receipts' => 'date',
        'expenses' => 'date',
        'stocks' => 'branch',
        'stock-receipts' => 'date',
        'top-products' => 'rank',
        'cashiers' => 'branch',
        'price-histories' => 'date',
        'sale-voids' => 'voided_at',
    ];
    $moneyKeys = [
        'amount', 'subtotal', 'discount', 'total', 'selling_price', 'net_sales',
        'cost', 'unit_cost', 'average_cost', 'inventory_value', 'gross_sales',
        'gross_profit', 'expenses', 'net_profit', 'profit', 'voided_value',
        'average', 'old_purchase', 'new_purchase', 'purchase_difference',
        'old_selling', 'new_selling', 'selling_difference',
    ];
    $quantityKeys = ['quantity', 'minimum', 'before', 'change', 'after'];
    $headingKey = $headingKeyByReport[$report['slug']] ?? $report['columns'][0]['key'];
    $eyebrowKey = $eyebrowKeyByReport[$report['slug']] ?? null;
    $headingColumn = collect($report['columns'])->firstWhere('key', $headingKey);
    $statusColumn = collect($report['columns'])->firstWhere('key', 'status');
@endphp

<div class="reports-list reports-list--{{ $report['slug'] }}" aria-label="Daftar {{ $report['title'] }} mobile">
    @foreach ($report['rows'] as $row)
        @php
            $statusValue = $statusColumn ? ($row[$statusColumn['key']] ?? '—') : null;
            $statusClass = match (true) {
                in_array($statusValue, ['Selesai', 'Disetujui', 'Aktif', 'Aman', 'Dikonfirmasi'], true) => 'badge-success',
                in_array($statusValue, ['Menunggu Persetujuan', 'Menunggu Pembatalan', 'Pending', 'Menipis', 'Belum dikonfirmasi'], true) => 'badge-warning',
                in_array($statusValue, ['Dibatalkan', 'Ditolak', 'Habis', 'Nonaktif'], true) => 'badge-danger',
                default => 'badge-neutral',
            };
            $actionColumns = collect($report['columns'])->filter(
                fn (array $column): bool => isset($column['link'])
                    && in_array($column['key'], ['detail', 'receipt'], true)
                    && filled($row[$column['link']] ?? null)
            );
        @endphp

        <article class="report-card {{ $report['slug'] === 'price-histories' ? 'report-card--timeline' : '' }}">
            <header class="report-card__header">
                <div>
                    @if ($eyebrowKey && filled($row[$eyebrowKey] ?? null))
                        <span class="report-card__eyebrow">
                            {{ $eyebrowKey === 'rank' ? 'Peringkat #'.$row[$eyebrowKey] : $row[$eyebrowKey] }}
                        </span>
                    @endif
                    <h2>
                        @if (isset($headingColumn['link']) && filled($row[$headingColumn['link']] ?? null))
                            <a href="{{ $row[$headingColumn['link']] }}">{{ $row[$headingKey] ?? '—' }}</a>
                        @else
                            {{ $row[$headingKey] ?? '—' }}
                        @endif
                    </h2>
                </div>
                @if ($statusValue)
                    <span class="badge report-card__status {{ $statusClass }}">{{ $statusValue }}</span>
                @endif
            </header>

            <dl class="report-card__body">
                @foreach ($report['columns'] as $column)
                    @continue($column['key'] === $headingKey)
                    @continue($column['key'] === $eyebrowKey)
                    @continue($column['key'] === 'status')
                    @continue($actionColumns->contains(fn (array $action): bool => $action['key'] === $column['key']))
                    @php
                        $value = $row[$column['key']] ?? '—';
                        $valueClass = in_array($column['key'], $moneyKeys, true)
                            ? 'report-card__value--money ui-currency'
                            : (in_array($column['key'], $quantityKeys, true) ? 'report-card__value--quantity ui-quantity' : '');
                    @endphp
                    <div class="report-card__row">
                        <dt>{{ $column['label'] }}</dt>
                        <dd class="{{ $valueClass }}">
                            @if (isset($column['link']) && filled($row[$column['link']] ?? null))
                                @if (($column['method'] ?? 'get') === 'post')
                                    <form method="POST" action="{{ $row[$column['link']] }}" target="_blank">
                                        @csrf
                                        <button class="report-card__text-action" type="submit">{{ $value }}</button>
                                    </form>
                                @else
                                    <a href="{{ $row[$column['link']] }}">{{ $value }}</a>
                                @endif
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if ($actionColumns->isNotEmpty())
                <footer class="report-card__footer">
                    @foreach ($actionColumns as $column)
                        @if (($column['method'] ?? 'get') === 'post')
                            <form method="POST" action="{{ $row[$column['link']] }}" target="_blank">
                                @csrf
                                <button class="btn btn-secondary" type="submit">{{ $row[$column['key']] }}</button>
                            </form>
                        @else
                            <a class="btn btn-secondary" href="{{ $row[$column['link']] }}">{{ $row[$column['key']] }}</a>
                        @endif
                    @endforeach
                </footer>
            @endif
        </article>
    @endforeach
</div>
