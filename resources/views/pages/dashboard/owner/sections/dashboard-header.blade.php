<header class="dashboard-header">
    <div>
        <p class="dashboard-eyebrow">Ringkasan seluruh operasional toko</p>
        <h1>Dashboard Owner</h1>
        <p class="dashboard-header__context">
            <span data-active-branch>{{ $dashboard['filters']['branch_name'] }}</span>
            <span aria-hidden="true">•</span>
            <span data-active-period>{{ $dashboard['filters']['period_label'] }}</span>
        </p>
    </div>

    <div class="dashboard-header__actions">
        <p class="dashboard-updated">
            Diperbarui pada
            <time data-generated-at datetime="{{ $dashboard['generated_at'] }}">
                {{ $dashboard['generated_at_formatted'] }}
            </time>
        </p>
        <button class="btn btn-secondary" type="button" data-dashboard-refresh>
            Perbarui Data
        </button>
    </div>
</header>
