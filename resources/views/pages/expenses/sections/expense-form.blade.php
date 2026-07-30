@php($editingExpense = $expense !== null)
<form
    class="card form-card expense-form"
    method="POST"
    enctype="multipart/form-data"
    action="{{ $editingExpense ? route('expenses.update', $expense) : route('expenses.store') }}"
    data-expense-form
>
    @csrf
    @if ($editingExpense) @method('PUT') @endif

    <div class="form-grid">
        @if ($editingExpense)
            <div class="form-group">
                <label class="form-label" for="expense-branch-readonly">Cabang</label>
                <input class="form-control" id="expense-branch-readonly" value="{{ $expense->branch->name }}" disabled>
                <small class="form-hint">Cabang tidak dapat diubah setelah dicatat.</small>
            </div>
        @elseif (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="branch_id">Cabang *</label>
                <select class="form-control @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" @error('branch_id') aria-describedby="expense-branch-error" aria-invalid="true" @enderror required>
                    <option value="">Pilih cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id')<span class="form-error" id="expense-branch-error">{{ $message }}</span>@enderror
            </div>
        @else
            <div class="form-group">
                <label class="form-label" for="expense-branch-account">Cabang</label>
                <input class="form-control" id="expense-branch-account" value="{{ auth()->user()->branch?->name ?? 'Cabang belum ditetapkan' }}" disabled>
            </div>
        @endif

        <div class="form-group">
            <label class="form-label" for="expense_category_id">Kategori *</label>
            <select class="form-control @error('expense_category_id') is-invalid @enderror" id="expense_category_id" name="expense_category_id" @error('expense_category_id') aria-describedby="expense-category-error" aria-invalid="true" @enderror required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense?->expense_category_id) === (string) $category->id)>
                        {{ $category->name }}{{ ! $category->is_active ? ' (tidak aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('expense_category_id')<span class="form-error" id="expense-category-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="expense_date">Tanggal Pengeluaran *</label>
            <input class="form-control @error('expense_date') is-invalid @enderror" id="expense_date" name="expense_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString()) }}" @error('expense_date') aria-describedby="expense-date-error" aria-invalid="true" @enderror required>
            @error('expense_date')<span class="form-error" id="expense-date-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="amount">Jumlah *</label>
            <div class="expense-money-input"><span aria-hidden="true">Rp</span><input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" inputmode="numeric" autocomplete="off" value="{{ old('amount', \App\Support\Format\Rupiah::input($expense?->amount)) }}" aria-describedby="expense-amount-help @error('amount') expense-amount-error @enderror" @error('amount') aria-invalid="true" @enderror data-expense-amount required></div>
            <small class="form-hint" id="expense-amount-help">Gunakan Rupiah tanpa desimal, contoh 150.000.</small>
            @error('amount')<span class="form-error" id="expense-amount-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group form-grid__full">
            <label class="form-label" for="description">Deskripsi *</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" minlength="5" maxlength="1000" @error('description') aria-describedby="expense-description-error" aria-invalid="true" @enderror required>{{ old('description', $expense?->description) }}</textarea>
            @error('description')<span class="form-error" id="expense-description-error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group form-grid__full">
            <label class="form-label" for="proof">Bukti Pengeluaran</label>
            <input class="form-control @error('proof') is-invalid @enderror" id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="expense-proof-help expense-proof-name @error('proof') expense-proof-error @enderror" @error('proof') aria-invalid="true" @enderror data-expense-proof>
            <small class="form-hint" id="expense-proof-help">JPG, JPEG, PNG, atau WEBP. Maksimal 3 MB.</small>
            <span class="expense-proof-file-name" id="expense-proof-name" data-expense-proof-name>Belum ada file baru dipilih.</span>
            @error('proof')<span class="form-error" id="expense-proof-error">{{ $message }}</span>@enderror
            <div class="expense-proof-preview" data-expense-proof-preview hidden>
                <img alt="Pratinjau bukti pengeluaran" data-expense-proof-preview-image>
            </div>
        </div>
    </div>

    @if ($editingExpense && $expense->proof_file)
        <div class="expense-current-proof">
            <div>
                <span>Bukti saat ini masih tersimpan.</span>
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->proof_file) }}" target="_blank" rel="noopener noreferrer">Buka bukti lama</a>
            </div>
            @can('removeProof', $expense)
                <button class="btn btn-sm btn-danger" type="button" data-expense-remove-proof data-action="{{ route('expenses.proof.destroy', $expense) }}">Hapus Bukti</button>
            @endcan
        </div>
    @endif

    <div class="form-actions">
        <a class="btn btn-secondary" href="{{ $editingExpense ? route('expenses.show', $expense) : route('expenses.index') }}">Batal</a>
        <button class="btn btn-primary" type="submit" data-submit-button>{{ $editingExpense ? 'Simpan Perubahan' : 'Simpan Pengeluaran' }}</button>
    </div>
</form>
