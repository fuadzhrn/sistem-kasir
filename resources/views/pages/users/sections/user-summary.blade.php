<section class="summary-grid" aria-label="Ringkasan pengguna">
    <article class="card summary-card"><span>Total hasil</span><strong>{{ $users->total() }}</strong></article>
    <article class="card summary-card"><span>Aktif pada halaman</span><strong>{{ $users->getCollection()->where('is_active', true)->count() }}</strong></article>
    <article class="card summary-card"><span>Nonaktif pada halaman</span><strong>{{ $users->getCollection()->where('is_active', false)->count() }}</strong></article>
</section>
