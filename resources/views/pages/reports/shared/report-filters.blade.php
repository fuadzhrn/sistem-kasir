@php
    $options = $report['filter_options'];
    $sortLabels = [
        'date'=>'Tanggal','invoice'=>'Nomor Nota','product'=>'Produk','net_sales'=>'Penjualan Bersih',
        'total'=>'Total','status'=>'Status','cost'=>'HPP','profit'=>'Laba','margin'=>'Margin',
        'period'=>'Periode','branch'=>'Cabang','net_profit'=>'Laba Bersih','amount'=>'Nominal',
        'category'=>'Kategori','quantity'=>'Quantity','number'=>'Nomor Dokumen','supplier'=>'Supplier',
        'type'=>'Tipe','receipts'=>'Jumlah Nota','cashier'=>'Kasir','average'=>'Rata-rata Nota',
        'selling_change'=>'Perubahan Harga Jual','purchase_change'=>'Perubahan Harga Beli',
    ];
    $sortByReport = [
        'sales'=>['date','invoice','product','net_sales'],
        'receipts'=>['date','invoice','total','status'],
        'cost-of-goods-sold'=>['date','invoice','product','cost'],
        'gross-profit'=>['date','invoice','net_sales','profit','margin'],
        'net-profit'=>['period','branch','net_sales','net_profit'],
        'expenses'=>['date','amount','status','category'],
        'stocks'=>['status','product','quantity','branch'],
        'stock-receipts'=>['date','number','supplier','cost'],
        'stock-movements'=>['date','product','type'],
        'top-products'=>['net_sales','quantity','receipts','product'],
        'branches'=>['branch','net_sales','net_profit','receipts'],
        'cashiers'=>['cashier','net_sales','receipts','average'],
        'price-histories'=>['date','product','selling_change','purchase_change'],
        'sale-voids'=>['date','invoice','total','profit'],
    ];
    if ($report['slug'] === 'price-histories' && auth()->user()->isAdmin()) {
        $sortByReport['price-histories'] = ['date','product','selling_change'];
    }
    $statusByReport = [
        'sales' => ['completed'=>'Selesai','voided'=>'Dibatalkan'],
        'receipts' => ['completed'=>'Selesai','void_requested'=>'Menunggu Pembatalan','voided'=>'Dibatalkan'],
        'expenses' => ['pending'=>'Pending','approved'=>'Disetujui','rejected'=>'Ditolak'],
    ];
@endphp
<form id="report-filter-panel" class="report-filter card" method="GET" action="{{ route('reports.'.$report['slug'].'.index') }}" data-report-filter>
    <div class="report-filter__mobile-header">
        <div>
            <span>Persempit hasil</span>
            <h2>Filter {{ $report['title'] }}</h2>
        </div>
        <button class="report-filter__close" type="button" aria-label="Tutup filter laporan" data-report-filter-close>&times;</button>
    </div>
    @if ($report['slug'] !== 'stocks')
        <div class="form-group">
            <label class="form-label" for="report-period">Periode</label>
            <select class="form-control" id="report-period" name="period" data-report-period>
                @foreach (['today'=>'Hari Ini','this_week'=>'Minggu Ini','this_month'=>'Bulan Ini','this_year'=>'Tahun Ini','custom'=>'Rentang Tanggal'] as $value=>$label)
                    <option value="{{ $value }}" @selected(($report['filters']['period'] ?? 'this_month') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-filter__custom" data-report-custom @if (($report['filters']['period'] ?? null) !== 'custom') hidden @endif>
            <div class="form-group"><label class="form-label" for="report-date-from">Tanggal Mulai</label><input class="form-control" id="report-date-from" name="date_from" type="date" max="{{ now()->toDateString() }}" value="{{ $report['filters']['date_from'] ?? '' }}"></div>
            <div class="form-group"><label class="form-label" for="report-date-to">Tanggal Selesai</label><input class="form-control" id="report-date-to" name="date_to" type="date" max="{{ now()->toDateString() }}" value="{{ $report['filters']['date_to'] ?? '' }}"></div>
        </div>
    @endif
    @if (($options['branches'] ?? collect())->isNotEmpty())
        <div class="form-group"><label class="form-label" for="report-branch">Cabang</label><select class="form-control" id="report-branch" name="branch_id"><option value="">Semua Cabang</option>@foreach ($options['branches'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['branch_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['users'] ?? collect())->isNotEmpty() && in_array($report['slug'], ['sales','receipts','cost-of-goods-sold','gross-profit','sale-voids'], true))
        <div class="form-group"><label class="form-label" for="report-cashier">Kasir</label><select class="form-control" id="report-cashier" name="cashier_id"><option value="">Semua Kasir</option>@foreach ($options['users'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['cashier_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['users'] ?? collect())->isNotEmpty() && in_array($report['slug'], ['expenses','stock-receipts','stock-movements','price-histories'], true))
        @php
            $userFilter = match ($report['slug']) {
                'price-histories' => ['changed_by', 'Pengubah'],
                default => ['created_by', 'Pencatat'],
            };
        @endphp
        <div class="form-group"><label class="form-label" for="report-user">{{ $userFilter[1] }}</label><select class="form-control" id="report-user" name="{{ $userFilter[0] }}"><option value="">Semua Pengguna</option>@foreach ($options['users'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters'][$userFilter[0]]??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['users'] ?? collect())->isNotEmpty() && $report['slug'] === 'sale-voids')
        <div class="form-group"><label class="form-label" for="report-voider">Pembatal</label><select class="form-control" id="report-voider" name="voided_by"><option value="">Semua Pembatal</option>@foreach ($options['users'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['voided_by']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['products'] ?? collect())->isNotEmpty() && in_array($report['slug'], ['sales','cost-of-goods-sold','stock-receipts','stock-movements','price-histories'], true))
        <div class="form-group"><label class="form-label" for="report-product">Produk</label><select class="form-control" id="report-product" name="product_id"><option value="">Semua Produk</option>@foreach ($options['products'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['product_id']??'')===(string)$option->id)>{{ $option->code }} — {{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['categories'] ?? collect())->isNotEmpty())
        <div class="form-group"><label class="form-label" for="report-category">Kategori</label><select class="form-control" id="report-category" name="category_id"><option value="">Semua Kategori</option>@foreach ($options['categories'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['category_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['units'] ?? collect())->isNotEmpty())
        <div class="form-group"><label class="form-label" for="report-unit">Satuan</label><select class="form-control" id="report-unit" name="unit_id"><option value="">Semua Satuan</option>@foreach ($options['units'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['unit_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['payments'] ?? collect())->isNotEmpty())
        <div class="form-group"><label class="form-label" for="report-payment">Pembayaran</label><select class="form-control" id="report-payment" name="payment_method_id"><option value="">Semua Metode</option>@foreach ($options['payments'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['payment_method_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if (($options['expense_categories'] ?? collect())->isNotEmpty())
        <div class="form-group"><label class="form-label" for="report-expense-category">Kategori Pengeluaran</label><select class="form-control" id="report-expense-category" name="category_id"><option value="">Semua Kategori</option>@foreach ($options['expense_categories'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['category_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
    @endif
    @if ($report['slug'] === 'net-profit')
        <div class="form-group"><label class="form-label" for="report-granularity">Granularity</label><select class="form-control" id="report-granularity" name="granularity">@foreach (['daily'=>'Harian','weekly'=>'Mingguan','monthly'=>'Bulanan','yearly'=>'Tahunan'] as $value=>$label)<option value="{{ $value }}" @selected(($report['filters']['granularity']??$report['granularity'])===$value)>{{ $label }}</option>@endforeach</select></div>
    @endif
    @if (isset($statusByReport[$report['slug']]))
        <div class="form-group"><label class="form-label" for="report-status">Status</label><select class="form-control" id="report-status" name="status"><option value="all">Semua Status</option>@foreach ($statusByReport[$report['slug']] as $value=>$label)<option value="{{ $value }}" @selected(($report['filters']['status']??'all')===$value)>{{ $label }}</option>@endforeach</select></div>
    @endif
    @if ($report['slug'] === 'stocks')
        <div class="form-group"><label class="form-label" for="report-stock-status">Status Stok</label><select class="form-control" id="report-stock-status" name="stock_status"><option value="">Semua Status</option><option value="out" @selected(($report['filters']['stock_status']??null)==='out')>Habis</option><option value="low" @selected(($report['filters']['stock_status']??null)==='low')>Menipis</option><option value="safe" @selected(($report['filters']['stock_status']??null)==='safe')>Aman</option></select></div>
        <div class="form-group"><label class="form-label" for="report-product-status">Status Produk</label><select class="form-control" id="report-product-status" name="product_status"><option value="all">Semua Produk</option><option value="active" @selected(($report['filters']['product_status']??'all')==='active')>Aktif</option><option value="inactive" @selected(($report['filters']['product_status']??'all')==='inactive')>Nonaktif</option></select></div>
    @endif
    @if ($report['slug'] === 'stock-receipts')
        <div class="form-group"><label class="form-label" for="report-supplier">Supplier</label><input class="form-control" id="report-supplier" name="supplier" maxlength="100" value="{{ $report['filters']['supplier'] ?? '' }}" placeholder="Nama supplier"></div>
    @endif
    @if ($report['slug'] === 'stock-movements')
        <div class="form-group"><label class="form-label" for="report-movement-type">Tipe Movement</label><input class="form-control" id="report-movement-type" name="movement_type" maxlength="50" value="{{ $report['filters']['movement_type'] ?? '' }}" placeholder="Contoh: sale"></div>
        <div class="form-group"><label class="form-label" for="report-reference-type">Tipe Referensi</label><input class="form-control" id="report-reference-type" name="reference_type" maxlength="150" value="{{ $report['filters']['reference_type'] ?? '' }}" placeholder="Class referensi"></div>
    @endif
    @if ($report['slug'] === 'branches')
        <div class="form-group"><label class="form-label" for="report-branch-status">Status Cabang</label><select class="form-control" id="report-branch-status" name="branch_status"><option value="all">Semua Cabang</option><option value="active" @selected(($report['filters']['branch_status']??'all')==='active')>Aktif</option><option value="inactive" @selected(($report['filters']['branch_status']??'all')==='inactive')>Nonaktif</option></select></div>
    @endif
    @if ($report['slug'] === 'cashiers')
        @if (($options['roles'] ?? collect())->isNotEmpty())
            <div class="form-group"><label class="form-label" for="report-role">Role</label><select class="form-control" id="report-role" name="role_id"><option value="">Semua Role</option>@foreach ($options['roles'] as $option)<option value="{{ $option->id }}" @selected((string)($report['filters']['role_id']??'')===(string)$option->id)>{{ $option->name }}</option>@endforeach</select></div>
        @endif
        <div class="form-group"><label class="form-label" for="report-user-status">Status Pengguna</label><select class="form-control" id="report-user-status" name="user_status"><option value="all">Semua Pengguna</option><option value="active" @selected(($report['filters']['user_status']??'all')==='active')>Aktif</option><option value="inactive" @selected(($report['filters']['user_status']??'all')==='inactive')>Nonaktif</option></select></div>
    @endif
    @if ($report['slug'] === 'price-histories')
        <div class="form-group"><label class="form-label" for="report-change-type">Jenis Perubahan</label><select class="form-control" id="report-change-type" name="change_type"><option value="all">Semua</option><option value="selling" @selected(($report['filters']['change_type']??'all')==='selling')>Harga Jual</option>@if(auth()->user()->isOwner())<option value="purchase" @selected(($report['filters']['change_type']??'all')==='purchase')>Harga Beli</option>@endif</select></div>
    @endif
    <div class="form-group report-filter__search"><label class="form-label" for="report-search">Pencarian</label><input class="form-control" id="report-search" name="search" maxlength="100" value="{{ $report['filters']['search'] ?? '' }}" placeholder="Cari pada laporan"></div>
    <div class="form-group"><label class="form-label" for="report-sort">Urutkan</label><select class="form-control" id="report-sort" name="sort">@foreach ($sortByReport[$report['slug']] as $value)<option value="{{ $value }}" @selected(($report['filters']['sort']??'date')===$value)>{{ $sortLabels[$value] }}</option>@endforeach</select></div>
    <div class="form-group"><label class="form-label" for="report-direction">Arah</label><select class="form-control" id="report-direction" name="direction"><option value="desc" @selected(($report['filters']['direction']??'desc')==='desc')>Menurun</option><option value="asc" @selected(($report['filters']['direction']??'desc')==='asc')>Menanjak</option></select></div>
    <div class="form-group"><label class="form-label" for="report-per-page">Per Halaman</label><select class="form-control" id="report-per-page" name="per_page">@foreach ([25,50,100] as $value)<option value="{{ $value }}" @selected((int)($report['filters']['per_page']??25)===$value)>{{ $value }}</option>@endforeach</select></div>
    <div class="report-filter__actions"><button class="btn btn-primary" type="submit" data-report-submit>Terapkan Filter</button><a class="btn btn-ghost" href="{{ route('reports.'.$report['slug'].'.index') }}">Reset</a><button class="btn btn-secondary report-filter__mobile-cancel" type="button" data-report-filter-close>Tutup</button></div>
</form>
