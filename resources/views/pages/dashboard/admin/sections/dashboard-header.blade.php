<header class="dashboard-header admin-dashboard__header">
    <div>
        <p class="dashboard-eyebrow">Ringkasan operasional cabang</p>
        <h1>Dashboard Cabang</h1>
        <p class="dashboard-header__context">
            <strong data-active-branch>{{ $dashboard['filters']['branch_name'] }}</strong>
            <span aria-hidden="true">•</span>
            <span data-active-period>{{ $dashboard['filters']['period_label'] }}</span>
        </p>
        <p class="admin-dashboard__scope">
            Data dashboard dibatasi untuk Cabang
            <strong>{{ $dashboard['filters']['branch_name'] }}</strong>.
        </p>
    </div>

    <div class="dashboard-header__actions">
        <p class="dashboard-updated">
            Diperbarui pada
            <time data-generated-at datetime="{{ $dashboard['generated_at'] }}">
                {{ $dashboard['generated_at_formatted'] }}
            </time>
        </p>
        <button class="btn btn-secondary" type="button" data-dashboard-refresh>Perbarui Data</button>
    </div>
</header>
