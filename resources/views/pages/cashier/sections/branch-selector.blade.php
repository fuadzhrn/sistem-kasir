@if ($canSwitchBranch)
    <section class="cashier-branch-selector" aria-labelledby="cashier-branch-heading">
        <div>
            <p class="eyebrow">Konteks transaksi</p>
            <h1 id="cashier-branch-heading">{{ $branch ? 'Kasir '.$branch->name : 'Pilih Cabang Kasir' }}</h1>
            <p>{{ $branch ? 'Produk dan stok hanya berasal dari cabang ini.' : 'Pilih cabang aktif sebelum memuat produk.' }}</p>
        </div>
        <div class="form-group">
            <label class="form-label" for="cashier-branch">Cabang aktif</label>
            <select class="form-select" id="cashier-branch" data-branch-selector>
                <option value="">Pilih cabang</option>
                @foreach ($branches as $availableBranch)
                    <option value="{{ $availableBranch->id }}" @selected($branch?->id === $availableBranch->id)>
                        {{ $availableBranch->code }} — {{ $availableBranch->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </section>
@else
    <section class="cashier-branch-selector cashier-branch-selector--fixed" aria-label="Cabang kasir">
        <div>
            <p class="eyebrow">Cabang akun</p>
            <h1>{{ $branch->name }}</h1>
            <p>{{ $branch->code }} — cabang ditentukan secara aman dari akun Anda.</p>
        </div>
    </section>
@endif
