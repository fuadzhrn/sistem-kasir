<form
    action="{{ route('stock-adjustments.store') }}"
    method="POST"
    class="adjustment-form"
    data-adjustment-form
    data-branch-id="{{ $branch?->id }}"
    data-branch-name="{{ $branch ? $branch->code.' - '.$branch->name : '' }}"
    data-stock-quantities="{{ $stockQuantities->toJson() }}"
>
    @csrf
    <section class="card">
        <div class="card__header">
            <div><p class="eyebrow">Data penyesuaian</p><h3>Perubahan Stok</h3></div>
            <span class="badge badge-warning">Permanen</span>
        </div>
        <div class="card__body form-grid">
            @if (auth()->user()->isOwner())
                <div class="form-group">
                    <label class="form-label" for="adjustment-branch">Cabang <span class="form-required">*</span></label>
                    <select class="form-select @error('branch_id') is-error @enderror" id="adjustment-branch" name="branch_id" required data-adjustment-branch>
                        <option value="">Pilih cabang aktif</option>
                        @foreach ($branches as $formBranch)
                            <option value="{{ $formBranch->id }}" @selected((string) old('branch_id') === (string) $formBranch->id)>{{ $formBranch->code }} - {{ $formBranch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            @else
                <div class="form-group">
                    <label class="form-label" for="adjustment-branch-readonly">Cabang</label>
                    <input class="form-control" id="adjustment-branch-readonly" type="text" value="{{ $branch->code }} - {{ $branch->name }}" readonly>
                </div>
            @endif

            <div class="form-group">
                <label class="form-label" for="adjustment-product">Produk <span class="form-required">*</span></label>
                <select class="form-select @error('product_id') is-error @enderror" id="adjustment-product" name="product_id" required data-adjustment-product>
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
                <label class="form-label" for="adjustment-type">Jenis penyesuaian <span class="form-required">*</span></label>
                <select class="form-select @error('adjustment_type') is-error @enderror" id="adjustment-type" name="adjustment_type" required data-adjustment-type>
                    <option value="">Pilih jenis</option>
                    @foreach ($labels as $type => $label)
                        <option value="{{ $type }}" @selected(old('adjustment_type') === $type)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('adjustment_type')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group adjustment-current-stock">
                <span class="form-label">Stok saat ini</span>
                <strong data-current-stock>0</strong>
                <span data-current-unit></span>
                <span class="form-help">Nilai final dibaca ulang dan dikunci oleh server.</span>
            </div>

            <div class="form-group" data-quantity-group>
                <label class="form-label" for="adjustment-quantity">Quantity perubahan <span class="form-required">*</span></label>
                <input class="form-control @error('quantity') is-error @enderror" id="adjustment-quantity" name="quantity" type="number" value="{{ old('quantity') }}" min="0.001" max="999999999.999" step="0.001" inputmode="decimal" data-adjustment-quantity>
                @error('quantity')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group" data-target-group hidden>
                <label class="form-label" for="adjustment-target">Target quantity akhir <span class="form-required">*</span></label>
                <input class="form-control @error('target_quantity') is-error @enderror" id="adjustment-target" name="target_quantity" type="number" value="{{ old('target_quantity') }}" min="0" max="999999999.999" step="0.001" inputmode="decimal" data-adjustment-target disabled>
                @error('target_quantity')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group form-group--full">
                <label class="form-label" for="adjustment-reason">Alasan <span class="form-required">*</span></label>
                <textarea class="form-textarea @error('reason') is-error @enderror" id="adjustment-reason" name="reason" minlength="10" maxlength="1000" required data-adjustment-reason placeholder="Jelaskan hasil pemeriksaan atau kejadian secara spesifik.">{{ old('reason') }}</textarea>
                <span class="form-help">Minimal 10 karakter. Alasan akan disimpan pada dokumen dan stock movement.</span>
                @error('reason')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>

    @include('pages.stock-adjustments.sections.quantity-preview')

    <div class="adjustment-form__actions">
        <a class="btn btn-secondary" href="{{ route('stock-adjustments.index') }}">Batal</a>
        <button class="btn btn-primary" type="submit" data-adjustment-submit>Simpan Penyesuaian</button>
    </div>
</form>
