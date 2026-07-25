@php
    $branchesWithLow = $branchSummaries->where('low_count', '>', 0)->count();
    $branchesWithOut = $branchSummaries->where('out_count', '>', 0)->count();
    $lastUpdate = $branchSummaries->pluck('last_stock_update')->filter()->max();
@endphp

<section class="stock-summary-grid" aria-label="Ringkasan seluruh cabang">
    <article class="card stock-summary-card">
        <span>Cabang Aktif</span>
        <strong>{{ $branchSummaries->count() }}</strong>
    </article>
    <article class="card stock-summary-card">
        <span>Cabang dengan Stok Menipis</span>
        <strong>{{ $branchesWithLow }}</strong>
    </article>
    <article class="card stock-summary-card">
        <span>Cabang dengan Produk Habis</span>
        <strong>{{ $branchesWithOut }}</strong>
    </article>
    <article class="card stock-summary-card">
        <span>Perubahan Terakhir</span>
        <strong class="stock-summary-card__date">
            {{ $lastUpdate ? \Illuminate\Support\Carbon::parse($lastUpdate)->format('d M Y, H:i') : 'Belum ada' }}
        </strong>
    </article>
</section>

<section class="table-card" aria-label="Ringkasan stok per cabang">
    <div class="table-wrapper">
        <table class="table branch-stock-summary-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Cabang</th>
                    <th class="table-number">SKU Aktif</th>
                    <th class="table-number">Aman</th>
                    <th class="table-number">Menipis</th>
                    <th class="table-number">Habis</th>
                    <th>Pembaruan Terakhir</th>
                    <th class="table-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branchSummaries as $branch)
                    <tr>
                        <td><strong>{{ $branch->code }}</strong></td>
                        <td>{{ $branch->name }}</td>
                        <td class="table-number">{{ $branch->active_sku_count }}</td>
                        <td class="table-number"><span class="badge badge-success">{{ $branch->safe_count }}</span></td>
                        <td class="table-number"><span class="badge badge-warning">{{ $branch->low_count }}</span></td>
                        <td class="table-number"><span class="badge badge-danger">{{ $branch->out_count }}</span></td>
                        <td>
                            {{ $branch->last_stock_update
                                ? \Illuminate\Support\Carbon::parse($branch->last_stock_update)->format('d M Y, H:i')
                                : 'Belum ada perubahan' }}
                        </td>
                        <td class="table-actions">
                            <a class="btn btn-sm btn-outline" href="{{ route('stocks.index', ['branch_id' => $branch->id]) }}">
                                Lihat Stok
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="8">Belum ada cabang aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
