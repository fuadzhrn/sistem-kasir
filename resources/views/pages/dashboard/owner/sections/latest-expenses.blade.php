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
                        <td><time datetime="{{ $expense['expense_date_iso'] }}">{{ $expense['expense_date'] }}</time></td>
                        <td>{{ $expense['branch'] }}</td>
                        <td>{{ $expense['category'] }}</td>
                        <td><a href="{{ $expense['detail_url'] }}">{{ \Illuminate\Support\Str::limit($expense['description'], 70) }}</a></td>
                        <td>{{ $expense['creator'] }}</td>
                        <td>{{ $expense['amount_formatted'] }}</td>
                        <td><span class="badge badge-{{ $expense['status_variant'] }}">{{ $expense['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-empty">Belum ada pengeluaran pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
