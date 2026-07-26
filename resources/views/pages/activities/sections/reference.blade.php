<section class="card activities-detail-card">
    <h2>Referensi</h2>
    <dl class="activities-definition-list">
        <div><dt>Tipe</dt><dd>{{ $activity['reference_type'] ? class_basename($activity['reference_type']) : 'Tidak ada' }}</dd></div>
        <div><dt>ID</dt><dd>{{ $activity['reference_id'] ? '#'.$activity['reference_id'] : '—' }}</dd></div>
        <div><dt>Modul</dt><dd>{{ $activity['module_label'] }}</dd></div>
        <div><dt>Kode aksi</dt><dd><code>{{ $activity['action'] }}</code></dd></div>
    </dl>
</section>
