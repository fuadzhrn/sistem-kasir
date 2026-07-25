@php($editingCategory = $expenseCategory !== null)
<form class="card expense-category-form" method="POST" action="{{ $editingCategory ? route('expense-categories.update', $expenseCategory) : route('expense-categories.store') }}">
    @csrf
    @if ($editingCategory) @method('PUT') @endif
    <div class="form-group">
        <label class="form-label" for="name">Nama Kategori *</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" required maxlength="150" value="{{ old('name', $expenseCategory?->name) }}">
        @error('name')<span class="form-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="description">Deskripsi</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" maxlength="500">{{ old('description', $expenseCategory?->description) }}</textarea>
        @error('description')<span class="form-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-actions">
        <a class="btn btn-secondary" href="{{ route('expense-categories.index') }}">Batal</a>
        <button class="btn btn-primary" type="submit">{{ $editingCategory ? 'Simpan Perubahan' : 'Tambah Kategori' }}</button>
    </div>
</form>
