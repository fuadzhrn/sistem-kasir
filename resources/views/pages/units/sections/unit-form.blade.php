@php($editing = isset($unit))
<div class="form-grid">
    <div class="form-group"><label class="form-label" for="name">Nama Satuan <span aria-hidden="true">*</span></label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" maxlength="255" required value="{{ old('name', $unit->name ?? '') }}" autocomplete="off"><small class="form-help">Slug dibuat otomatis dari nama satuan.</small>@error('name')<span class="form-error">{{ $message }}</span>@enderror</div>
    <div class="form-group"><label class="form-label" for="symbol">Simbol</label><input class="form-control @error('symbol') is-invalid @enderror" id="symbol" name="symbol" type="text" maxlength="20" value="{{ old('symbol', $unit->symbol ?? '') }}" placeholder="Contoh: kg, ml, L">@error('symbol')<span class="form-error">{{ $message }}</span>@enderror</div>
</div>
<div class="alert alert-info form-note">Satu produk menggunakan satu satuan jual. Konversi satuan belum tersedia.</div>
<div class="form-actions"><a class="btn btn-secondary" href="{{ $editing ? route('units.show', $unit) : route('units.index') }}">Batal</a><button class="btn btn-primary" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Satuan' }}</button></div>
