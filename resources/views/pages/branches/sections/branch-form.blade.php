<section class="card form-card">
    @if (! $canChangeCode)
        <div class="alert alert-warning" role="status">
            <span class="alert__icon" aria-hidden="true">!</span>
            <div class="alert__content">
                <h4 class="alert__title">Kode cabang terkunci</h4>
                <p class="alert__message">Cabang sudah memiliki transaksi sehingga kode tidak dapat diubah.</p>
            </div>
        </div>
    @endif

    <form action="{{ $action }}" method="POST">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="code">Kode Cabang <span class="form-required">*</span></label>
                <input
                    class="form-control @error('code') is-error @enderror"
                    id="code"
                    name="code"
                    type="text"
                    value="{{ old('code', $branch?->code) }}"
                    maxlength="20"
                    autocomplete="off"
                    @readonly(! $canChangeCode)
                    required
                >
                @error('code')<span class="form-error">{{ $message }}</span>@enderror
                <span class="form-help">Gunakan huruf, angka, atau tanda hubung tanpa spasi.</span>
            </div>
            <div class="form-group">
                <label class="form-label" for="name">Nama Cabang <span class="form-required">*</span></label>
                <input class="form-control @error('name') is-error @enderror" id="name" name="name" type="text" value="{{ old('name', $branch?->name) }}" maxlength="255" required>
                @error('name')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">Nomor Telepon</label>
                <input class="form-control @error('phone') is-error @enderror" id="phone" name="phone" type="text" value="{{ old('phone', $branch?->phone) }}" maxlength="30">
                @error('phone')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group form-group--full">
                <label class="form-label" for="address">Alamat</label>
                <textarea class="form-textarea @error('address') is-error @enderror" id="address" name="address" maxlength="2000">{{ old('address', $branch?->address) }}</textarea>
                @error('address')<span class="form-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ $branch ? route('branches.show', $branch) : route('branches.index') }}">Batal</a>
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
        </div>
    </form>
</section>
