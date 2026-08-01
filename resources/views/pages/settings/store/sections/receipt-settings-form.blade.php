@php
    $receiptHasErrors = $errors->hasAny([
        'receipt_footer_message',
        'receipt_additional_information',
        'default_paper_width',
        'number_format',
        'number_prefix',
        'number_separator',
        'sequence_digits',
    ]);
    $selectedPaperWidth = (int) old('default_paper_width', $settings['receipt.default_paper_width']);
@endphp

<section class="card settings-section" id="pengaturan-struk">
    <div class="card__header settings-section__header">
        <div class="settings-section__heading">
            <span class="settings-section__number">03</span>
            <div>
                <h2>Pengaturan Struk</h2>
                <p>Atur informasi opsional, nomor nota, dan ukuran kertas untuk penggunaan berikutnya.</p>
            </div>
        </div>
        @if ($receiptHasErrors)
            <span class="badge badge-danger" role="status">Periksa input</span>
        @else
            <span class="badge badge-warning">Data baru</span>
        @endif
    </div>

    <form class="card__body settings-form" method="POST" action="{{ route('settings.store.receipt.update') }}" data-settings-form data-receipt-settings-form>
        @csrf
        @method('PUT')

        <fieldset class="settings-subsection" id="informasi-tambahan">
            <legend>Informasi Tambahan</legend>
            <p class="settings-subsection__description">Teks ini ditampilkan pada bagian bawah struk sesuai pengaturan yang tersimpan.</p>

            <div class="form-group">
                <label class="form-label" for="receipt_footer_message">Pesan Penutup</label>
                <textarea
                    class="form-textarea @error('receipt_footer_message') is-error @enderror"
                    id="receipt_footer_message"
                    name="receipt_footer_message"
                    maxlength="500"
                    rows="2"
                    data-preview-source="footer"
                    @error('receipt_footer_message') aria-invalid="true" aria-describedby="receipt_footer_message_error" @enderror
                >{{ old('receipt_footer_message', $settings['receipt.footer_message']) }}</textarea>
                @error('receipt_footer_message')<span class="form-error" id="receipt_footer_message_error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="receipt_additional_information">Informasi Tambahan</label>
                <textarea
                    class="form-textarea @error('receipt_additional_information') is-error @enderror"
                    id="receipt_additional_information"
                    name="receipt_additional_information"
                    maxlength="1000"
                    rows="3"
                    data-preview-source="additional"
                    aria-describedby="receipt_additional_information_help @error('receipt_additional_information') receipt_additional_information_error @enderror"
                    @error('receipt_additional_information') aria-invalid="true" @enderror
                >{{ old('receipt_additional_information', $settings['receipt.additional_information']) }}</textarea>
                <span class="form-help" id="receipt_additional_information_help">Teks biasa saja. HTML dan script tidak akan dijalankan.</span>
                @error('receipt_additional_information')<span class="form-error" id="receipt_additional_information_error">{{ $message }}</span>@enderror
            </div>
        </fieldset>

        @include('pages.settings.store.sections.receipt-visibility-settings')
        @include('pages.settings.store.sections.receipt-number-settings')

        <fieldset class="settings-fieldset settings-paper" id="ukuran-kertas">
            <legend>Ukuran Kertas Struk</legend>
            <p class="settings-subsection__description">Pilih ukuran default yang sesuai dengan printer thermal.</p>

            <div
                class="settings-paper__options @error('default_paper_width') is-error @enderror"
                role="radiogroup"
                aria-labelledby="paper_width_label"
                @error('default_paper_width') aria-describedby="default_paper_width_error" @enderror
            >
                <span class="sr-only" id="paper_width_label">Ukuran struk default</span>

                <label class="settings-paper__option">
                    <input
                        type="radio"
                        name="default_paper_width"
                        value="58"
                        data-preview-source="paper-width"
                        @checked($selectedPaperWidth === 58)
                    >
                    <span>
                        <strong>58 mm</strong>
                        <small>Untuk printer thermal kecil.</small>
                    </span>
                </label>

                <label class="settings-paper__option">
                    <input
                        type="radio"
                        name="default_paper_width"
                        value="80"
                        data-preview-source="paper-width"
                        @checked($selectedPaperWidth === 80)
                    >
                    <span>
                        <strong>80 mm</strong>
                        <small>Untuk printer thermal lebar.</small>
                    </span>
                </label>
            </div>
            @error('default_paper_width')<span class="form-error" id="default_paper_width_error">{{ $message }}</span>@enderror
        </fieldset>

        <div class="alert alert-info">Informasi utama transaksi seperti nomor nota, produk, total, dan metode pembayaran tetap selalu ditampilkan.</div>
        <p class="settings-note">Ukuran global digunakan sebagai default. Perangkat yang pernah memilih ukuran sendiri dapat tetap menggunakan preferensi browsernya.</p>

        <div class="settings-save">
            <button class="btn btn-primary" type="submit" data-settings-submit data-default-label="Simpan Pengaturan">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</section>
