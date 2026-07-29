<article class="dashboard-table-card card dashboard-table-card--wide">
    <header class="dashboard-section-heading">
        <div>
            <h2>Pengeluaran Terbaru</h2>
            <p>Daftar dapat memuat status menunggu, disetujui, dan ditolak.</p>
        </div>
        <a href="{{ route('expenses.index') }}">Lihat pengeluaran</a>
    </header>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Cabang</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Deskripsi</th>
                    <th scope="col">Pencatat</th>
                    <th scope="col">Nominal</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody data-latest-expenses>
                @forelse ($dashboard['latest_expenses'] as $expense)
                    <tr>
                        <td data-label="Tanggal"><time datetime="{{ $expense['expense_date_iso'] }}">{{ $expense['expense_date'] }}</time></td>
                        <td data-label="Cabang">{{ $expense['branch'] }}</td>
                        <td data-label="Kategori">{{ $expense['category'] }}</td>
                        <td data-label="Deskripsi"><a href="{{ $expense['detail_url'] }}">{{ \Illuminate\Support\Str::limit($expense['description'], 70) }}</a></td>
                        <td data-label="Pencatat">{{ $expense['creator'] }}</td>
                        <td data-label="Jumlah">{{ $expense['amount_formatted'] }}</td>
                        <td data-label="Status">
                            <span class="badge badge-{{ $expense['status_variant'] }}">{{ $expense['status'] }}</span>
                            <a class="dashboard-mobile-detail" href="{{ $expense['detail_url'] }}">Lihat Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">Belum ada pengeluaran pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
