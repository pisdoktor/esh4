<?php

declare(strict_types=1);

/**
 * Birleşik ayar sekmelerinde modül anahtarı switch.
 *
 * @var string $moduleSwitchKey
 * @var string $moduleSwitchLabel
 * @var string $moduleSwitchDescription
 * @var bool $moduleSwitchEnabled
 */
$moduleSwitchKey = $moduleSwitchKey ?? '';
$moduleSwitchLabel = $moduleSwitchLabel ?? '';
$moduleSwitchDescription = $moduleSwitchDescription ?? '';
$moduleSwitchEnabled = $moduleSwitchEnabled ?? false;
if ($moduleSwitchKey === '') {
    return;
}
?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-toggle-on text-primary me-2 opacity-75"></i>Modül durumu</h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="flex-grow-1" style="min-width: 12rem;">
                <div class="fw-semibold"><?= htmlspecialchars($moduleSwitchLabel, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($moduleSwitchDescription !== ''): ?>
                    <div class="small text-muted"><?= htmlspecialchars($moduleSwitchDescription, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       name="modules[<?= htmlspecialchars($moduleSwitchKey, ENT_QUOTES, 'UTF-8') ?>]"
                       id="mod-dedicated-<?= htmlspecialchars($moduleSwitchKey, ENT_QUOTES, 'UTF-8') ?>"
                       value="1"
                       <?= $moduleSwitchEnabled ? 'checked' : '' ?>>
                <label class="form-check-label small" for="mod-dedicated-<?= htmlspecialchars($moduleSwitchKey, ENT_QUOTES, 'UTF-8') ?>">
                    <?= $moduleSwitchEnabled ? 'Açık' : 'Kapalı' ?>
                </label>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">Kapalıyken aşağıdaki ayarlar kaydedilir; modül kapısı devreye girmez.</p>
    </div>
</div>
