<?php
declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\FormHelper;
use App\Helpers\UsbsComplianceHelper;

if (!AuthHelper::sessionIsAdmin() || !UsbsComplianceHelper::enabled()) {
    return;
}

/** @var object|null $visit */
$visit = $visit ?? null;
$labels = UsbsComplianceHelper::visitRefLabels();
$durum = UsbsComplianceHelper::normalizeBildirimDurum((string) ($visit->usbs_bildirim_durum ?? ''));
$durumOptions = [
    '' => '— (otomatik)',
    'pending' => 'Bekliyor',
    'sent' => 'Gönderildi',
    'failed' => 'Başarısız',
    'skipped' => 'Atlandı',
];
?>
<div class="card border-0 shadow-sm mb-3 border-start border-info border-3 esh-usbs-ref-panel">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 fw-bold text-info">
            <i class="fa-solid fa-heart-pulse me-2"></i>USBS / e-Nabız referansları
        </h6>
    </div>
    <div class="card-body py-3">
        <p class="small text-muted mb-3">
            İzlem bildirimi ve e-Reçete referansları (manuel köprü).
            <a href="<?= htmlspecialchars(esh_url('UsbsCompliance', 'index'), ENT_QUOTES, 'UTF-8') ?>">Alan eşlemesi</a>
        </p>
        <div class="row g-2">
            <div class="col-md-6">
                <?= FormHelper::fieldInput('usbs_bildirim_ref', $labels['usbs_bildirim_ref'], (string) ($visit->usbs_bildirim_ref ?? ''), [
                    'col' => '',
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm font-monospace',
                    'placeholder' => 'USBS bildirim no',
                    'maxlength' => 64,
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= FormHelper::fieldInput('erecete_ref', $labels['erecete_ref'], (string) ($visit->erecete_ref ?? ''), [
                    'col' => '',
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-control-sm font-monospace',
                    'placeholder' => 'e-Reçete no',
                    'maxlength' => 64,
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= FormHelper::fieldSelect('usbs_bildirim_durum', $labels['usbs_bildirim_durum'], $durum, $durumOptions, [
                    'col' => '',
                    'labelClass' => 'form-label small text-muted mb-1',
                    'class' => 'form-select-sm',
                ]) ?>
            </div>
            <?php if (!empty($visit->usbs_bildirim_at)): ?>
            <div class="col-md-6 d-flex align-items-end">
                <p class="small text-muted mb-2">
                    Son bildirim: <?= htmlspecialchars((string) $visit->usbs_bildirim_at, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
