<?php
/**
 * @var int $clinicalOverdueHighRiskCount
 * @var int $clinicalOverdueDays
 */
$clinicalOverdueHighRiskCount = (int) ($clinicalOverdueHighRiskCount ?? 0);
$clinicalOverdueDays = (int) ($clinicalOverdueDays ?? 30);
if ($clinicalOverdueHighRiskCount < 1) {
    return;
}
$listUrl = \App\Helpers\UrlHelper::fromRequestParams([
    'controller' => 'Stats',
    'action' => 'clinicalDecisionSupport',
]);
?>
<div class="card shadow-sm border-0 mb-4 border-start border-4 border-danger">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
        <h6 class="mb-0 fw-bold text-danger">
            <i class="fa-solid fa-user-doctor me-2 opacity-75"></i>Klinik karar desteği
        </h6>
        <span class="badge rounded-pill bg-danger"><?= $clinicalOverdueHighRiskCount ?></span>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3 mb-lg-2">
            Son <?= (int) $clinicalOverdueDays ?> günde izlenmemiş ve Braden / düşme / beslenme / Barthel skorları yüksek risk gösteren aktif hasta.
        </p>
        <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
            <i class="fa-solid fa-list me-1"></i>Listeyi aç
        </a>
    </div>
</div>
