<form
    action="{{ route('stock-transfers.store') }}"
    method="POST"
    class="transfer-form"
    data-transfer-form
    data-source-id="{{ $source?->id }}"
    data-stock-quantities="{{ $stockQuantities->toJson() }}"
>
    @csrf
    <section class="card">
        <div class="card__header">
            <div><p class="eyebrow">Data permintaan</p><h3>Rute dan Produk</h3></div>
            <span class="badge badge-warning">Menunggu Owner</span>
        </div>
        <div class="card__body form-grid">
            @if (auth()->user()->isOwner())
                <div class="form-group">
                    <label class="form-label" for="transfer-source">Cabang asal <span class="form-required">*</span></label>
                    <select class="form-select @error('from_branch_id') is-error @enderror" id="transfer-source" name="from_branch_id" required data-transfer-source>
                        <option value="">Pilih cabang asal</option>
                        @foreach ($branches as $formBranch)
                            <option value="{{ $formBranch->id }}" @selected((string) old('from_branch_id') === (string) $formBranch->id)>{{ $formBranch->code }} - {{ $formBranch->name }}</option>
                        @endforeach
                    </select>
                    @error('from_branch_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            @else
                <div class="form-group">
                    <label class="form-label" for="transfer-source-readonly">Cabang asal</label>
                    <input class="form-control" id="transfer-source-readonly" type="text" value="{{ $source->code }} - {{ $source->name }}" readonly>
                </div>
            @endif

            <div class="form-group">
                <label class="form-label" for="transfer-destination">Cabang tujuan <span class="form-required">*</span></label>
                <select class="form-select @error('to_branch_id') is-error @enderror" id="transfer-destination" name="to_branch_id" required data-transfer-destination>
                    <option value="">Pilih cabang tujuan</option>
                    @foreach ($branches as $formBranch)
                        <option value="{{ $formBranch->id }}" @selected((string) old('to_branch_id') === (string) $formBranch->id)>{{ $formBranch->code }} - {{ $formBranch->name }}</option>
                    @endforeach
                </select>
                @error('to_branch_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="transfer-product">Produk <span class="form-required">*</span></label>
                <select class="form-select @error('product_id') is-error @enderror" id="transfer-product" name="product_id" required data-transfer-product>
                    <option value="">Pilih produk aktif</option>
                    @foreach ($products as $formProduct)
                        <option value="{{ $formProduct->id }}" data-unit="{{ $formProduct->unit->symbol ?: $formProduct->unit->name }}" @selected((string) old('product_id') === (string) $formProduct->id)>
                            {{ $formProduct->code }} - {{ $formProduct->name }}{{ $formProduct->brand ? ' / '.$formProduct->brand : '' }}{{ $formProduct->size ? ' / '.$formProduct->size : '' }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="transfer-quantity">Quantity <span class="form-required">*</span></label>
                <input class="form-control @error('quantity') is-error @enderror" id="transfer-quantity" name="quantity" type="text" value="{{ \App\Support\Format\Quantity::inputValue(old('quantity')) }}" inputmode="decimal" required data-transfer-quantity data-quantity-input>
                @error('quantity')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group form-group--full">
                <label class="form-label" for="transfer-notes">Catatan permintaan <span class="form-required">*</span></label>
                <textarea class="form-textarea @error('notes') is-error @enderror" id="transfer-notes" name="notes" minlength="10" maxlength="1000" required placeholder="Jelaskan kebutuhan mutasi secara spesifik.">{{ old('notes') }}</textarea>
                <span class="form-help">Minimal 10 karakter. Catatan tersimpan pada dokumen audit.</span>
                @error('notes')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>

    @include('pages.stock-transfers.sections.stock-preview')

    <div class="transfer-form__actions">
        <a class="btn btn-secondary" href="{{ route('stock-transfers.index') }}">Batal</a>
        <button class="btn btn-primary" type="submit" data-transfer-submit>Simpan Permintaan</button>
    </div>
</form>
