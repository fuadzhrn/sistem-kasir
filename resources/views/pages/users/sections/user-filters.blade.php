<section class="card filter-card">
    <form class="module-filters user-filters" action="{{ route('users.index') }}" method="GET">
        <div class="form-group module-filters__search">
            <label class="form-label" for="search">Pencarian</label>
            <input class="form-control" id="search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Nama, username, atau email">
        </div>
        <div class="form-group">
            <label class="form-label" for="role">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="">Semua role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->slug }}" @selected($roleFilter === $role->slug)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="branch">Cabang</label>
            <select class="form-select" id="branch" name="branch">
                <option value="">Semua cabang</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($branchFilter === $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="module-filters__actions">
            <button class="btn btn-secondary" type="submit">Terapkan</button>
            <a class="btn btn-ghost" href="{{ route('users.index') }}">Reset</a>
        </div>
    </form>
</section>
