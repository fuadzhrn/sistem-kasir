<header class="cashier-dashboard__welcome card">
    <div class="cashier-dashboard__greeting">
        <p class="cashier-dashboard__eyebrow">Selamat Datang,</p>
        <h1>{{ $dashboard['cashier']['name'] }}</h1>
        <p>Siap melayani transaksi hari ini.</p>
    </div>
    <dl class="cashier-dashboard__identity">
        <div class="cashier-dashboard__branch">
            <dt>Cabang Aktif</dt>
            <dd>{{ $dashboard['cashier']['branch_name'] }}</dd>
        </div>
        <div class="cashier-dashboard__identity-item">
            <dt>Tanggal</dt>
            <dd>{{ now()->translatedFormat('l, d F Y') }}</dd>
        </div>
        <div class="cashier-dashboard__identity-item">
            <dt>Status Akun</dt>
            <dd><span class="badge badge-success">{{ $dashboard['cashier']['status'] }}</span></dd>
        </div>
    </dl>
</header>
