<fieldset class="settings-fieldset" id="nomor-nota">
    <legend>Format Nomor Nota</legend>
    <div class="form-grid">
        <div class="form-group form-group--full">
            <label class="form-label" for="number_format">Pola Nomor Nota</label>
            <select class="form-select @error('number_format') is-error @enderror" id="number_format" name="number_format" data-number-format>
                @php($selectedFormat = old('number_format', $settings['receipt.number_format']))
                <option value="branch_date_sequence" @selected($selectedFormat === 'branch_date_sequence')>UTM-YYYYMMDD-0001</option>
                <option value="prefix_branch_date_sequence" @selected($selectedFormat === 'prefix_branch_date_sequence')>INV-UTM-YYYYMMDD-0001</option>
                <option value="branch_date_sequence_slash" @selected($selectedFormat === 'branch_date_sequence_slash')>UTM/YYYYMMDD/0001</option>
                <option value="prefix_branch_date_sequence_slash" @selected($selectedFormat === 'prefix_branch_date_sequence_slash')>INV/UTM/YYYYMMDD/0001</option>
            </select>
            @error('number_format')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="number_prefix">Prefix</label>
            <input class="form-control @error('number_prefix') is-error @enderror" id="number_prefix" name="number_prefix" type="text" maxlength="10" value="{{ old('number_prefix', $settings['receipt.number_prefix']) }}" placeholder="INV" data-number-prefix>
            @error('number_prefix')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="number_separator">Pemisah</label>
            <select class="form-select @error('number_separator') is-error @enderror" id="number_separator" name="number_separator" data-number-separator>
                <option value="-" @selected(old('number_separator', $settings['receipt.number_separator']) === '-')>-</option>
                <option value="/" @selected(old('number_separator', $settings['receipt.number_separator']) === '/')>/</option>
            </select>
            @error('number_separator')<span class="form-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="sequence_digits">Jumlah Digit Urutan</label>
            <select class="form-select @error('sequence_digits') is-error @enderror" id="sequence_digits" name="sequence_digits" data-sequence-digits>
                @foreach ([4, 5, 6] as $digits)
                    <option value="{{ $digits }}" @selected((int) old('sequence_digits', $settings['receipt.sequence_digits']) === $digits)>{{ $digits }} digit</option>
                @endforeach
            </select>
            @error('sequence_digits')<span class="form-error">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="number-preview">
        <span>Preview nomor nota</span>
        <strong data-number-preview>UTM-{{ now()->format('Ymd') }}-0001</strong>
    </div>
    <p class="settings-note">Perubahan format hanya berlaku untuk transaksi baru. Nomor nota lama tidak akan diubah. Urutan tetap dihitung per cabang dan per tanggal.</p>
</fieldset>
