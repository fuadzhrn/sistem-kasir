<div class="form-group">
    <label class="form-label" for="role_id">Role <span class="form-required">*</span></label>
    <select class="form-select @error('role_id') is-error @enderror" id="role_id" name="role_id" data-role-select required>
        <option value="">Pilih role</option>
        @foreach ($roles as $role)
            <option
                value="{{ $role->id }}"
                data-role-slug="{{ $role->slug }}"
                @selected((string) old('role_id', $user?->role_id) === (string) $role->id)
            >
                {{ $role->name }}
            </option>
        @endforeach
    </select>
    @error('role_id')<span class="form-error">{{ $message }}</span>@enderror
</div>

<div class="form-group" data-branch-field>
    <label class="form-label" for="branch_id">Cabang <span class="form-required" data-branch-required>*</span></label>
    <select class="form-select @error('branch_id') is-error @enderror" id="branch_id" name="branch_id" data-branch-select>
        <option value="">Pilih cabang aktif</option>
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user?->branch_id) === (string) $branch->id)>
                {{ $branch->code }} — {{ $branch->name }}
            </option>
        @endforeach
    </select>
    @error('branch_id')<span class="form-error">{{ $message }}</span>@enderror
    <span class="form-help" data-branch-help>Owner tidak memakai cabang; Admin dan Kasir wajib memiliki cabang aktif.</span>
</div>
