<section class="card table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Cabang / Kategori</th>
                    <th>Deskripsi</th>
                    <th class="text-right">Jumlah</th>
                    <th>Pencatat</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->translatedFormat('d M Y') }}</td>
                        <td>
                            <strong>{{ $expense->branch->name }}</strong>
                            <span class="table-secondary">{{ $expense->expenseCategory->name }}</span>
                        </td>
                        <td>
                            {{ \Illuminate\Support\Str::limit($expense->description, 70) }}
                            @if ($expense->proof_file)
                                <span class="table-secondary">Bukti tersedia</span>
                            @endif
                        </td>
                        <td class="text-right"><strong>{{ \App\Support\Format\Rupiah::format($expense->amount) }}</strong></td>
                        <td>
                            {{ $expense->creator->name }}
                            <span class="table-secondary">{{ $expense->created_at->translatedFormat('d M Y H:i') }}</span>
                        </td>
                        <td>@include('pages.expenses.sections.expense-status-badge')</td>
                        <td>
                            <div class="action-group">
                                <a class="btn btn-sm btn-secondary" href="{{ route('expenses.show', $expense) }}">Detail</a>
                                @can('update', $expense)
                                    <a class="btn btn-sm btn-secondary" href="{{ route('expenses.edit', $expense) }}">Ubah</a>
                                @endcan
                                @can('approve', $expense)
                                    <button class="btn btn-sm btn-success" type="button" data-expense-approve data-action="{{ route('expenses.approve', $expense) }}" data-description="{{ \Illuminate\Support\Str::limit($expense->description, 80) }}" data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}">Setujui</button>
                                    <button class="btn btn-sm btn-danger" type="button" data-expense-reject data-action="{{ route('expenses.reject', $expense) }}" data-description="{{ \Illuminate\Support\Str::limit($expense->description, 80) }}" data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}">Tolak</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><h3>Belum ada pengeluaran</h3><p>Catat pengeluaran operasional pertama atau ubah filter pencarian.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($expenses->hasPages())
        <div class="pagination-wrap">{{ $expenses->links() }}</div>
    @endif
</section>
