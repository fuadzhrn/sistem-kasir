@php($editingCategory = $expenseCategory !== null)
<form class="card expense-category-form" method="POST" action="{{ $editingCategory ? route('expense-categories.update', $expenseCategory) : route('expense-categories.store') }}" data-expense-category-form>
    @csrf
    @if ($editingCategory) @method('PUT') @endif
    <div class="form-group">
        <label class="form-label" for="name">Nama Kategori *</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" required maxlength="150" value="{{ old('name', $expenseCategory?->name) }}" @error('name') aria-describedby="expense-category-name-error" aria-invalid="true" @enderror>
        @error('name')<span class="form-error" id="expense-category-name-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-group">
        <label class="form-label" for="description">Deskripsi</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" maxlength="500" @error('description') aria-describedby="expense-category-description-error" aria-invalid="true" @enderror>{{ old('description', $expenseCategory?->description) }}</textarea>
        @error('description')<span class="form-error" id="expense-category-description-error">{{ $message }}</span>@enderror
    </div>
    <div class="form-actions">
        <a class="btn btn-secondary" href="{{ route('expense-categories.index') }}">Batal</a>
        <button class="btn btn-primary" type="submit" data-expense-category-submit>{{ $editingCategory ? 'Simpan Perubahan' : 'Tambah Kategori' }}</button>
    </div>
</form>
