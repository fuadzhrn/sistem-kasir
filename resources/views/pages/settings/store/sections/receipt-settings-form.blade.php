<section class="card settings-section" id="pengaturan-struk">
    <div class="card__header settings-section__header">
        <div><span class="settings-section__number">03</span><h2>Pengaturan Struk</h2></div>
        <span class="badge badge-warning">Data baru</span>
    </div>
    <form class="card__body settings-form" method="POST" action="{{ route('settings.store.receipt.update') }}" data-settings-form data-receipt-settings-form>
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group form-group--full">
                <label class="form-label" for="receipt_footer_message">Pesan Penutup</label>
                <textarea class="form-textarea @error('receipt_footer_message') is-error @enderror" id="receipt_footer_message" name="receipt_footer_message" maxlength="500" rows="2" data-preview-source="footer">{{ old('receipt_footer_message', $settings['receipt.footer_message']) }}</textarea>
                @error('receipt_footer_message')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group form-group--full">
                <label class="form-label" for="receipt_additional_information">Informasi Tambahan</label>
                <textarea class="form-textarea @error('receipt_additional_information') is-error @enderror" id="receipt_additional_information" name="receipt_additional_information" maxlength="1000" rows="3" data-preview-source="additional">{{ old('receipt_additional_information', $settings['receipt.additional_information']) }}</textarea>
                <span class="form-help">Teks biasa saja. HTML dan script tidak akan dijalankan.</span>
                @error('receipt_additional_information')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="default_paper_width">Ukuran Struk Default</label>
                <select class="form-select @error('default_paper_width') is-error @enderror" id="default_paper_width" name="default_paper_width" data-preview-source="paper-width">
                    @foreach ([58, 80] as $width)
                        <option value="{{ $width }}" @selected((int) old('default_paper_width', $settings['receipt.default_paper_width']) === $width)>{{ $width }} mm</option>
                    @endforeach
                </select>
                @error('default_paper_width')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        @include('pages.settings.store.sections.receipt-visibility-settings')
        @include('pages.settings.store.sections.receipt-number-settings')

        <div class="alert alert-info">Informasi utama transaksi seperti nomor nota, produk, total, dan metode pembayaran tetap selalu ditampilkan.</div>
        <p class="settings-note">Ukuran global digunakan sebagai default. Perangkat yang pernah memilih ukuran sendiri dapat tetap menggunakan preferensi browsernya.</p>
        <div class="settings-save"><button class="btn btn-primary" type="submit">Simpan Pengaturan Struk</button></div>
    </form>
</section>
