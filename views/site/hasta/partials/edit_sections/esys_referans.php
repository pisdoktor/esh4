<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\EsysComplianceHelper;
use App\Helpers\FormHelper;

if (!AuthHelper::sessionIsAdmin() || !EsysComplianceHelper::enabled()) {
    return;
}

/** @var object $patient */
$patient = $patient ?? (object) [];
$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');
$labels = EsysComplianceHelper::patientRefLabels();
?>
<div class="row g-3 esh-esys-ref-panel">
    <div class="col-12">
        <p class="small text-muted mb-2">
            <i class="fa-solid fa-link me-1"></i>
            ESYS’teki karşılık kayıt numaraları (manuel köprü).
            <a href="<?= htmlspecialchars(esh_url('EsysCompliance', 'index'), ENT_QUOTES, 'UTF-8') ?>">Alan eşlemesi</a>
        </p>
    </div>
    <?php foreach ($labels as $name => $label): ?>
    <div class="col-md-6">
        <?= FormHelper::fieldInput($name, $label, (string) ($patient->{$name} ?? ''), [
            'id' => $eshFormIdPrefix . $name,
            'col' => '',
            'labelClass' => 'form-label fw-semibold small',
            'class' => 'form-control-sm font-monospace',
            'placeholder' => 'ESYS referans no',
            'maxlength' => 64,
        ]) ?>
    </div>
    <?php endforeach; ?>
</div>
