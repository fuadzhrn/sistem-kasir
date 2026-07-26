<header class="cashier-dashboard__welcome card">
    <div>
        <p class="cashier-dashboard__eyebrow">Ruang kerja transaksi Anda</p>
        <h1>Dashboard Kasir</h1>
        <p>
            Selamat datang, <strong>{{ $dashboard['cashier']['name'] }}</strong>.
            Anda bertugas di <strong>{{ $dashboard['cashier']['branch_name'] }}</strong>.
        </p>
    </div>
    <dl class="cashier-dashboard__identity">
        <div>
            <dt>Tanggal</dt>
            <dd>{{ now()->translatedFormat('l, d F Y') }}</dd>
        </div>
        <div>
            <dt>Status Akun</dt>
            <dd><span class="badge badge-success">{{ $dashboard['cashier']['status'] }}</span></dd>
        </div>
    </dl>
</header>
