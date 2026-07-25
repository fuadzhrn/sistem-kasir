<header class="receipt-header">
    <p class="receipt-store-name">{{ config('app.name') }}</p>
    <h1>{{ $sale->branch?->name ?? 'Cabang Toko' }}</h1>
    @if ($sale->branch?->address)<p>{{ $sale->branch->address }}</p>@endif
    @if ($sale->branch?->phone)<p>Telp. {{ $sale->branch->phone }}</p>@endif
    <dl>
        <div><dt>Nomor Nota</dt><dd>{{ $sale->invoice_number }}</dd></div>
        <div><dt>Tanggal</dt><dd>{{ $sale->transaction_date->locale('id')->translatedFormat('d F Y, H.i') }}</dd></div>
        <div><dt>Kasir</dt><dd>{{ $sale->cashier?->name ?? 'Pengguna historis' }}</dd></div>
        <div><dt>Status</dt><dd>{{ $sale->statusLabel() }}</dd></div>
    </dl>
</header>
