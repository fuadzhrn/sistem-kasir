<section class="card table-card expense-table-card">
    <div class="expense-table-wrapper">
        <table class="expense-table">
            <colgroup>
                <col class="expense-table__column-date">
                <col class="expense-table__column-branch">
                <col class="expense-table__column-description">
                <col class="expense-table__column-amount">
                <col class="expense-table__column-recorder">
                <col class="expense-table__column-status">
                <col class="expense-table__column-actions">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Cabang / Kategori</th>
                    <th scope="col">Deskripsi</th>
                    <th class="expense-table__amount" scope="col">Jumlah</th>
                    <th scope="col">Pencatat</th>
                    <th class="expense-table__status" scope="col">Status</th>
                    <th class="expense-table__actions-heading" scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="expense-table__date">{{ $expense->expense_date->translatedFormat('d M Y') }}</td>
                        <td class="expense-table__branch">
                            <strong>{{ $expense->branch->name }}</strong>
                            <span class="expense-table__category">{{ $expense->expenseCategory->name }}</span>
                        </td>
                        <td class="expense-table__description">
                            <span class="expense-table__description-text">{{ \Illuminate\Support\Str::limit($expense->description, 140) }}</span>
                            @if ($expense->proof_file)
                                <span class="expense-table__proof">Bukti tersedia</span>
                            @endif
                        </td>
                        <td class="expense-table__amount"><strong>{{ \App\Support\Format\Rupiah::format($expense->amount) }}</strong></td>
                        <td class="expense-table__recorder">
                            <strong>{{ $expense->creator->name }}</strong>
                            <time class="expense-table__recorded-at" datetime="{{ $expense->created_at->toIso8601String() }}">
                                {{ $expense->created_at->translatedFormat('d M Y H:i') }}
                            </time>
                        </td>
                        <td class="expense-table__status">@include('pages.expenses.sections.expense-status-badge')</td>
                        <td class="expense-table__actions-cell">
                            <div class="expense-table__actions">
                                <a class="btn btn-sm btn-secondary" href="{{ route('expenses.show', $expense) }}">Detail</a>
                                @can('update', $expense)
                                    <a class="btn btn-sm btn-secondary" href="{{ route('expenses.edit', $expense) }}">Ubah</a>
                                @endcan
                                @can('approve', $expense)
                                    <button class="btn btn-sm btn-success" type="button" data-expense-approve data-action="{{ route('expenses.approve', $expense) }}" data-description="{{ $expense->description }}" data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}" data-branch="{{ $expense->branch->name }}" data-category="{{ $expense->expenseCategory->name }}" data-date="{{ $expense->expense_date->translatedFormat('d F Y') }}" data-creator="{{ $expense->creator->name }}" data-proof="{{ $expense->proof_file ? 'Tersedia' : 'Tidak ada' }}">Setujui</button>
                                    <button class="btn btn-sm btn-danger" type="button" data-expense-reject data-action="{{ route('expenses.reject', $expense) }}" data-description="{{ $expense->description }}" data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}" data-branch="{{ $expense->branch->name }}" data-category="{{ $expense->expenseCategory->name }}" data-date="{{ $expense->expense_date->translatedFormat('d F Y') }}" data-creator="{{ $expense->creator->name }}" data-proof="{{ $expense->proof_file ? 'Tersedia' : 'Tidak ada' }}">Tolak</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="expense-table__empty-row"><td colspan="7"><div class="empty-state"><h3>Belum ada pengeluaran</h3><p>Catat pengeluaran operasional pertama atau ubah filter pencarian.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('pages.expenses.sections.expense-card-list')
    {{ $expenses->onEachSide(1)->links('components.pagination', ['itemLabel' => 'pengeluaran']) }}
</section>
