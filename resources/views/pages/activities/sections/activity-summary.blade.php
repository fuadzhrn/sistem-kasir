<section class="activities-summary" aria-label="Ringkasan aktivitas">
    <article class="card activities-summary__item">
        <span>Total hasil</span>
        <strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong>
    </article>
    <article class="card activities-summary__item">
        <span>Aktivitas hari ini</span>
        <strong>{{ number_format($summary['today'], 0, ',', '.') }}</strong>
    </article>
    <article class="card activities-summary__item">
        <span>Login gagal</span>
        <strong>{{ number_format($summary['failed_logins'], 0, ',', '.') }}</strong>
    </article>
    <article class="card activities-summary__item">
        <span>Pelaku berbeda</span>
        <strong>{{ number_format($summary['users'], 0, ',', '.') }}</strong>
    </article>
</section>
