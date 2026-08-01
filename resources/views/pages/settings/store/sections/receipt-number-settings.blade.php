<fieldset class="settings-fieldset" id="nomor-nota">
    <legend>Pengaturan Nomor Nota</legend>
    <p class="settings-subsection__description">Susun pola nomor yang akan digunakan pada transaksi baru.</p>

    <div class="form-grid settings-number-grid">
        <div class="form-group form-group--full">
            <label class="form-label" for="number_format">Pola Nomor Nota</label>
            <select
                class="form-select @error('number_format') is-error @enderror"
                id="number_format"
                name="number_format"
                data-number-format
                @error('number_format') aria-invalid="true" aria-describedby="number_format_error" @enderror
            >
                @php($selectedFormat = old('number_format', $settings['receipt.number_format']))
                <option value="branch_date_sequence" @selected($selectedFormat === 'branch_date_sequence')>UTM-YYYYMMDD-0001</option>
                <option value="prefix_branch_date_sequence" @selected($selectedFormat === 'prefix_branch_date_sequence')>INV-UTM-YYYYMMDD-0001</option>
                <option value="branch_date_sequence_slash" @selected($selectedFormat === 'branch_date_sequence_slash')>UTM/YYYYMMDD/0001</option>
                <option value="prefix_branch_date_sequence_slash" @selected($selectedFormat === 'prefix_branch_date_sequence_slash')>INV/UTM/YYYYMMDD/0001</option>
            </select>
            @error('number_format')<span class="form-error" id="number_format_error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="number_prefix">Prefix</label>
            <input
                class="form-control @error('number_prefix') is-error @enderror"
                id="number_prefix"
                name="number_prefix"
                type="text"
                maxlength="10"
                value="{{ old('number_prefix', $settings['receipt.number_prefix']) }}"
                placeholder="INV"
                data-number-prefix
                @error('number_prefix') aria-invalid="true" aria-describedby="number_prefix_error" @enderror
            >
            @error('number_prefix')<span class="form-error" id="number_prefix_error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="number_separator">Pemisah</label>
            <select
                class="form-select @error('number_separator') is-error @enderror"
                id="number_separator"
                name="number_separator"
                data-number-separator
                @error('number_separator') aria-invalid="true" aria-describedby="number_separator_error" @enderror
            >
                <option value="-" @selected(old('number_separator', $settings['receipt.number_separator']) === '-')>-</option>
                <option value="/" @selected(old('number_separator', $settings['receipt.number_separator']) === '/')>/</option>
            </select>
            @error('number_separator')<span class="form-error" id="number_separator_error">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="sequence_digits">Jumlah Digit Urutan</label>
            <select
                class="form-select @error('sequence_digits') is-error @enderror"
                id="sequence_digits"
                name="sequence_digits"
                data-sequence-digits
                @error('sequence_digits') aria-invalid="true" aria-describedby="sequence_digits_error" @enderror
            >
                @foreach ([4, 5, 6] as $digits)
                    <option value="{{ $digits }}" @selected((int) old('sequence_digits', $settings['receipt.sequence_digits']) === $digits)>{{ $digits }} digit</option>
                @endforeach
            </select>
            @error('sequence_digits')<span class="form-error" id="sequence_digits_error">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="number-preview" aria-live="polite">
        <span>Contoh nomor nota</span>
        <strong data-number-preview>UTM-{{ now()->format('Ymd') }}-0001</strong>
    </div>

    <div class="settings-impact-notice" role="note">
        <strong>Data lama tetap aman.</strong>
        <p>Perubahan format nomor nota hanya berlaku untuk transaksi baru. Nomor nota transaksi lama tidak akan diubah, termasuk saat dicetak ulang atau ditampilkan pada laporan lama.</p>
    </div>
    <p class="settings-note">Urutan tetap dihitung per cabang dan per tanggal sesuai mekanisme yang sudah berjalan.</p>
</fieldset>
