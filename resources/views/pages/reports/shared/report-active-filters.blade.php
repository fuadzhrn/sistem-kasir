@php
    $filterOptions = $report['filter_options'];
    $filterValueLabels = [
        'completed' => 'Selesai',
        'void_requested' => 'Menunggu Pembatalan',
        'voided' => 'Dibatalkan',
        'pending' => 'Menunggu Persetujuan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'out' => 'Habis',
        'low' => 'Menipis',
        'safe' => 'Aman',
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'selling' => 'Harga Jual',
        'purchase' => 'Harga Beli',
        'daily' => 'Harian',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
        'asc' => 'Menanjak',
        'desc' => 'Menurun',
    ];
    $optionGroupsByLabel = [
        'ID Kasir' => 'users',
        'ID Pencatat' => 'users',
        'ID Pengubah' => 'users',
        'ID Pembatal' => 'users',
        'ID Produk' => 'products',
        'ID Kategori' => isset($filterOptions['expense_categories']) ? 'expense_categories' : 'categories',
        'ID Satuan' => 'units',
        'ID Pembayaran' => 'payments',
        'ID Role' => 'roles',
    ];
@endphp

<section class="reports-filter-summary" aria-label="Filter laporan aktif">
    <span class="reports-filter-summary__label">Filter aktif</span>
    <div class="reports-filter-summary__chips">
        <span class="reports-filter-chip"><strong>Periode:</strong> {{ $report['period_label'] }}</span>
        <span class="reports-filter-chip"><strong>Cabang:</strong> {{ $report['branch_name'] }}</span>
        @foreach ($report['active_filters'] as $filter)
            @php
                $value = $filterValueLabels[$filter['value']] ?? $filter['value'];
                $optionGroup = $optionGroupsByLabel[$filter['label']] ?? null;

                if ($optionGroup && isset($filterOptions[$optionGroup])) {
                    $option = $filterOptions[$optionGroup]->firstWhere('id', (int) $filter['value']);
                    $value = $option?->name ?? $value;
                }
            @endphp
            <span class="reports-filter-chip">
                <strong>{{ str_replace('ID ', '', $filter['label']) }}:</strong> {{ $value }}
            </span>
        @endforeach
    </div>
</section>
