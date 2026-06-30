<?php
/**
 * Hasta kartı — pasif / dosya kapalı uyarı şeridi (Patient&action=view).
 *
 * @var object $hasta
 * @var string $pasifnedeni Controller’da hesaplanmış etiket
 */
if (empty($hasta->pasif)) {
    return;
}

$pasifTarihTr = !empty($hasta->pasiftarihi)
    ? \App\Helpers\DateHelper::toTr((string) $hasta->pasiftarihi)
    : '—';
$nedenLabel = trim((string) ($pasifnedeni ?? ''));
if ($nedenLabel === '') {
    $nedenLabel = 'Pasif';
}
$nedenEsc = htmlspecialchars($nedenLabel, ENT_QUOTES, 'UTF-8');
$pasifKod = trim((string) ($hasta->pasif ?? ''));
$alertLevel = in_array($pasifKod, ['1', '-1', '4', '5'], true) ? 'danger' : 'warning';
$iconClass = $alertLevel === 'danger' ? 'fa-lock' : 'fa-circle-exclamation';
$eshNakilDurum = \App\Helpers\PatientNakilRequest::nakilViewSummaryForPatient($hasta);
?>
<div class="esh-pasif-dosya-banner mb-4" role="region" aria-label="Dosya durumu uyarısı">
    <div class="alert alert-<?= $alertLevel ?> esh-status-banner-alert mb-0 d-flex align-items-start gap-3 shadow-sm border-start border-4" role="alert">
        <span class="esh-status-banner-alert__icon flex-shrink-0 mt-1" aria-hidden="true">
            <i class="fa-solid <?= $iconClass ?> fa-lg"></i>
        </span>
        <div class="esh-status-banner-alert__body min-w-0">
            <div class="fw-bold fs-6 mb-1">Dosya kapalı (<?= $nedenEsc ?>)</div>
            <div class="mb-0">
                <span class="text-muted">Pasif tarihi:</span>
                <strong class="font-monospace"><?= htmlspecialchars($pasifTarihTr, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if ($eshNakilDurum !== null): ?>
            <div class="small mt-2 mb-0">
                <span class="text-muted">Nakil:</span>
                <strong class="text-body"><i class="fa-solid fa-building-circle-arrow-right me-1"></i><?= htmlspecialchars($eshNakilDurum, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
