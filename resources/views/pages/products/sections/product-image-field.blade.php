<div class="product-image-field">
    <img src="{{ isset($product) && $product->image_path ? asset('storage/'.$product->image_path) : asset('assets/images/placeholders/product-placeholder.svg') }}" alt="Preview foto produk" data-image-preview>
    <div class="form-group"><label class="form-label" for="image">{{ isset($product) && $product->image_path ? 'Ganti Foto' : 'Pilih Foto' }}</label><input class="form-control @error('image') is-invalid @enderror" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-input><small class="form-help">JPG, JPEG, PNG, atau WEBP. Maksimal 3 MB.</small>@error('image')<span class="form-error">{{ $message }}</span>@enderror</div>
</div>
