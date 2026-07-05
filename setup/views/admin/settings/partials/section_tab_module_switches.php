<?php

declare(strict_types=1);

/**
 * Ayar sekmesine bağlı modül aç/kapa anahtarları (SettingsNavCatalog::MODULES_ON_DEDICATED_TABS).
 *
 * @var string $moduleSwitchTab
 */
use App\Helpers\AppSettings;
use App\Helpers\SettingsNavCatalog;

$moduleSwitchTab = $moduleSwitchTab ?? ($activeTab ?? '');
if ($moduleSwitchTab === '') {
    return;
}

$moduleKeys = SettingsNavCatalog::dedicatedModulesForTab($moduleSwitchTab);
if ($moduleKeys === []) {
    return;
}

$registry = AppSettings::registry();
$rows = [];
foreach ($moduleKeys as $moduleKey) {
    $entry = $registry[$moduleKey] ?? null;
    if (!is_array($entry) || empty($entry['toggleable'])) {
        continue;
    }
    $rows[] = [
        'key' => $moduleKey,
        'label' => (string) ($entry['label'] ?? $moduleKey),
        'description' => (string) ($entry['description'] ?? ''),
        'enabled' => AppSettings::isModuleEnabled($moduleKey),
    ];
}
if ($rows === []) {
    return;
}
?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-toggle-on text-primary me-2 opacity-75"></i>Modül durumu</h5>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <?php foreach ($rows as $row): ?>
                <?php
                $mKey = (string) ($row['key'] ?? '');
                $mEnabled = !empty($row['enabled']);
                ?>
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
                    <div class="flex-grow-1" style="min-width: 12rem;">
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($row['label'] ?? $mKey), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (($row['description'] ?? '') !== ''): ?>
                            <div class="small text-muted"><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="modules[<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>]"
                               id="mod-dedicated-<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>"
                               value="1"
                               <?= $mEnabled ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="mod-dedicated-<?= htmlspecialchars($mKey, ENT_QUOTES, 'UTF-8') ?>">
                            <?= $mEnabled ? 'Açık' : 'Kapalı' ?>
                        </label>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="small text-muted mb-0 px-3 py-3 border-top">Kapalıyken aşağıdaki ayarlar kaydedilir; modül kapısı devreye girmez.</p>
    </div>
</div>
