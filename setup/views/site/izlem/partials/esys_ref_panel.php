<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\EsysComplianceHelper;
use App\Helpers\FormHelper;

if (!AuthHelper::sessionIsAdmin() || !EsysComplianceHelper::enabled()) {
    return;
}

/** @var object|null $visit */
$visit = $visit ?? null;
$labels = EsysComplianceHelper::visitRefLabels();
$hasKons = false;
if ($visit !== null && !empty($visit->yapilan)) {
    $hasKons = \App\Helpers\VisitIslemHelper::yapilanCsvContainsIslem(
        (string) $visit->yapilan,
        \App\Helpers\VisitIslemHelper::konsultasyonIslemId()
    );
}
?>
<div class="card border-0 shadow-sm mb-3 border-start border-secondary border-3 esh-esys-ref-panel">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 fw-bold text-secondary">
            <i class="fa-solid fa-link me-2"></i>ESYS referansları
        </h6>
    </div>
    <div class="card-body py-3">
        <p class="small text-muted mb-3">
            Resmi entegrasyon öncesi manuel eşleştirme.
            <a href="<?= htmlspecialchars(esh_url('EsysCompliance', 'index'), ENT_QUOTES, 'UTF-8') ?>">Alan eşlemesi</a>
        </p>
        <div class="row g-2">
            <div class="col-md-6">
                <?= FormHelper::fieldInput('esys_izlem_ref', $labels['esys_izlem_ref'], (string) ($visit->esys_izlem_ref ?? ''), [
                    'col' => '',
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm font-monospace',
                    'placeholder' => 'ESYS izlem no',
                    'maxlength' => 64,
                ]) ?>
            </div>
            <?php if ($hasKons): ?>
            <div class="col-md-6">
                <?= FormHelper::fieldInput('esys_konsultasyon_ref', $labels['esys_konsultasyon_ref'], (string) ($visit->esys_konsultasyon_ref ?? ''), [
                    'col' => '',
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm font-monospace',
                    'placeholder' => 'ESYS konsültasyon no',
                    'maxlength' => 64,
                ]) ?>
            </div>
            <?php else: ?>
            <input type="hidden" name="esys_konsultasyon_ref" value="<?= htmlspecialchars((string) ($visit->esys_konsultasyon_ref ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
        </div>
    </div>
</div>
