@php($editing = isset($category))
<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="name">Nama Kategori <span aria-hidden="true">*</span></label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" maxlength="255" required value="{{ old('name', $category->name ?? '') }}" autocomplete="off">
        <small class="form-help">Slug dibuat otomatis dari nama kategori.</small>
        @error('name')<span class="form-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-group form-grid__full">
        <label class="form-label" for="description">Deskripsi</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" maxlength="2000">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')<span class="form-error">{{ $message }}</span>@enderror
    </div>
</div>
<div class="form-actions">
    <a class="btn btn-secondary" href="{{ $editing ? route('categories.show', $category) : route('categories.index') }}">Batal</a>
    <button class="btn btn-primary" type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Kategori' }}</button>
</div>
