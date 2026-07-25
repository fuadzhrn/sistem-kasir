<section class="card filter-card" aria-label="Filter satuan"><form class="module-filters" method="GET" action="{{ route('units.index') }}">
    <div class="form-group"><label class="form-label" for="unit-search">Pencarian</label><input class="form-control" id="unit-search" name="search" type="search" maxlength="100" value="{{ $search }}" placeholder="Nama, simbol, atau slug"></div>
    <div class="form-group"><label class="form-label" for="unit-status">Status</label><select class="form-control" id="unit-status" name="status"><option value="">Semua status</option><option value="active" @selected($status === 'active')>Aktif</option><option value="inactive" @selected($status === 'inactive')>Nonaktif</option></select></div>
    <div class="module-filters__actions"><button class="btn btn-primary" type="submit">Terapkan</button><a class="btn btn-secondary" href="{{ route('units.index') }}">Reset</a></div>
</form></section>
