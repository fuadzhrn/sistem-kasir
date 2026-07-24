@if (session('status'))
    <div class="alert alert--success" role="status">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert--danger" role="alert">
        <p>Periksa kembali data yang dimasukkan.</p>
    </div>
@endif
