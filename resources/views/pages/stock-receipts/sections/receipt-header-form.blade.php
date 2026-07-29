<section class="card receipt-header-card">
    <div class="card__header">
        <div>
            <p class="eyebrow">Informasi dokumen</p>
            <h3>Header Penerimaan</h3>
        </div>
        <span class="badge badge-info">Nomor dibuat otomatis</span>
    </div>
    <div class="card__body form-grid">
        @if (auth()->user()->isOwner())
            <div class="form-group">
                <label class="form-label" for="receipt-branch">Cabang <span class="form-required">*</span></label>
                <select
                    class="form-select @error('branch_id') is-error @enderror"
                    id="receipt-branch"
                    name="branch_id"
                    required
                    data-receipt-branch
                    @error('branch_id') aria-describedby="receipt-branch-error" aria-invalid="true" @enderror
                >
                    <option value="">Pilih cabang aktif</option>
                    @foreach ($branches as $formBranch)
                        <option value="{{ $formBranch->id }}" @selected((string) old('branch_id') === (string) $formBranch->id)>
                            {{ $formBranch->code }} - {{ $formBranch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')<span class="form-error" id="receipt-branch-error">{{ $message }}</span>@enderror
            </div>
        @else
            <div class="form-group">
                <label class="form-label" for="receipt-branch-readonly">Cabang</label>
                <input class="form-control" id="receipt-branch-readonly" type="text" value="{{ $branch->code }} - {{ $branch->name }}" readonly>
                <span class="form-help">Cabang ditentukan dari akun Admin.</span>
            </div>
        @endif
        <div class="form-group">
            <label class="form-label" for="receipt-date">Tanggal penerimaan <span class="form-required">*</span></label>
            <input
                class="form-control @error('receipt_date') is-error @enderror"
                id="receipt-date"
                name="receipt_date"
                type="date"
                value="{{ old('receipt_date', now()->toDateString()) }}"
                max="{{ now()->toDateString() }}"
                required
                data-receipt-date
                @error('receipt_date') aria-describedby="receipt-date-error" aria-invalid="true" @enderror
            >
            @error('receipt_date')<span class="form-error" id="receipt-date-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="receipt-supplier">Supplier</label>
            <input
                class="form-control @error('supplier_name') is-error @enderror"
                id="receipt-supplier"
                name="supplier_name"
                type="text"
                value="{{ old('supplier_name') }}"
                maxlength="255"
                placeholder="Opsional"
                data-receipt-supplier
                @error('supplier_name') aria-describedby="receipt-supplier-error" aria-invalid="true" @enderror
            >
            @error('supplier_name')<span class="form-error" id="receipt-supplier-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="receipt-notes">Catatan</label>
            <textarea
                class="form-textarea @error('notes') is-error @enderror"
                id="receipt-notes"
                name="notes"
                maxlength="1000"
                placeholder="Opsional"
                data-receipt-notes
                @error('notes') aria-describedby="receipt-notes-error" aria-invalid="true" @enderror
            >{{ old('notes') }}</textarea>
            @error('notes')<span class="form-error" id="receipt-notes-error">{{ $message }}</span>@enderror
        </div>
    </div>
</section>
