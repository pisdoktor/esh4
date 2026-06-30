<?php
$archiveSelectedMahalleler = is_array($filters['mahalle'] ?? null) ? $filters['mahalle'] : [];
$archiveFilterExpanded = trim((string) ($filters['isim'] ?? '')) !== ''
    || trim((string) ($filters['soyisim'] ?? '')) !== ''
    || !empty($archiveSelectedMahalleler);
?>
<div class="esh-page esh-page--list esh-page-archive container-fluid py-4">
    <div class="row g-3 align-items-start">
<?php include __DIR__ . '/partials/filter_form.php'; ?>
<?php include __DIR__ . '/partials/results_table_shell.php'; ?>
    </div>
</div>
