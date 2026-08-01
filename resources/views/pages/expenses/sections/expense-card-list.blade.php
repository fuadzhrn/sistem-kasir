<div class="expense-card-list" aria-label="Daftar pengeluaran">
    @forelse ($expenses as $expense)
        @php
            $canUpdateExpense = \Illuminate\Support\Facades\Gate::allows('update', $expense);
            $canApproveExpense = \Illuminate\Support\Facades\Gate::allows('approve', $expense);
            $proofUrl = $expense->proof_file
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($expense->proof_file)
                : null;
        @endphp
        <article class="expense-card">
            <header class="expense-card__header">
                <div>
                    <span>Tanggal Pengeluaran</span>
                    <time datetime="{{ $expense->expense_date->toDateString() }}">
                        {{ $expense->expense_date->translatedFormat('d F Y') }}
                    </time>
                </div>
                @include('pages.expenses.sections.expense-status-badge')
            </header>

            <dl class="expense-card__body">
                <div><dt>Cabang</dt><dd>{{ $expense->branch->name }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $expense->expenseCategory->name ?? '—' }}</dd></div>
                <div class="expense-card__description"><dt>Deskripsi</dt><dd>{{ $expense->description }}</dd></div>
                <div><dt>Dicatat oleh</dt><dd>{{ $expense->creator->name }}</dd></div>
                <div class="expense-card__amount">
                    <dt>Jumlah</dt>
                    <dd class="ui-currency">{{ \App\Support\Format\Rupiah::format($expense->amount) }}</dd>
                </div>
            </dl>

            <footer class="expense-card__footer">
                <a class="btn btn-secondary" href="{{ route('expenses.show', $expense) }}">Detail Pengeluaran</a>
                @if ($canUpdateExpense || $canApproveExpense || $proofUrl)
                    <details class="expense-card__actions" data-expense-action-menu>
                        <summary class="btn btn-outline expense-card__action-toggle" aria-expanded="false">Tindakan</summary>
                        <div class="expense-card__action-menu" role="menu">
                            @if ($proofUrl)
                                <a class="btn btn-secondary" href="{{ $proofUrl }}" target="_blank" rel="noopener noreferrer" role="menuitem">Lihat Bukti</a>
                            @endif
                            @if ($canUpdateExpense)
                                <a class="btn btn-secondary" href="{{ route('expenses.edit', $expense) }}" role="menuitem">Ubah</a>
                            @endif
                            @if ($canApproveExpense)
                                <button
                                    class="btn btn-success"
                                    type="button"
                                    role="menuitem"
                                    data-expense-approve
                                    data-action="{{ route('expenses.approve', $expense) }}"
                                    data-description="{{ $expense->description }}"
                                    data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}"
                                    data-branch="{{ $expense->branch->name }}"
                                    data-category="{{ $expense->expenseCategory->name }}"
                                    data-date="{{ $expense->expense_date->translatedFormat('d F Y') }}"
                                    data-creator="{{ $expense->creator->name }}"
                                    data-proof="{{ $expense->proof_file ? 'Tersedia' : 'Tidak ada' }}"
                                >Setujui</button>
                                <button
                                    class="btn btn-danger"
                                    type="button"
                                    role="menuitem"
                                    data-expense-reject
                                    data-action="{{ route('expenses.reject', $expense) }}"
                                    data-description="{{ $expense->description }}"
                                    data-amount="{{ \App\Support\Format\Rupiah::format($expense->amount) }}"
                                    data-branch="{{ $expense->branch->name }}"
                                    data-category="{{ $expense->expenseCategory->name }}"
                                    data-date="{{ $expense->expense_date->translatedFormat('d F Y') }}"
                                    data-creator="{{ $expense->creator->name }}"
                                    data-proof="{{ $expense->proof_file ? 'Tersedia' : 'Tidak ada' }}"
                                >Tolak</button>
                            @endif
                        </div>
                    </details>
                @endif
            </footer>
        </article>
    @empty
        <div class="empty-state expenses-empty">
            <h3>Belum ada data Pengeluaran</h3>
            <p>Pengeluaran yang dicari tidak ditemukan atau belum tersedia pada periode ini.</p>
            <div>
                <a class="btn btn-secondary" href="{{ route('expenses.index') }}">Reset Filter</a>
                @can('create', \App\Models\Expense::class)
                    <a class="btn btn-primary" href="{{ route('expenses.create') }}">Tambah Pengeluaran</a>
                @endcan
            </div>
        </div>
    @endforelse
</div>
